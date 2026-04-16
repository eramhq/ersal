<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Tipax;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Catalog\Branch;
use Eram\Ersal\Contracts\ShipmentInterface;
use Eram\Ersal\Contracts\ShippingInterface;
use Eram\Ersal\Contracts\SupportsBranches;
use Eram\Ersal\Contracts\SupportsCOD;
use Eram\Ersal\Contracts\SupportsLabel;
use Eram\Ersal\Contracts\SupportsPickup;
use Eram\Ersal\Event\ShipmentCancelled;
use Eram\Ersal\Event\ShipmentCreated;
use Eram\Ersal\Event\ShipmentQuoted;
use Eram\Ersal\Event\ShipmentTracked;
use Eram\Ersal\Http\EventDispatcher;
use Eram\Ersal\Http\HttpClient;
use Eram\Ersal\Http\Logger;
use Eram\Ersal\Money\Amount;
use Eram\Ersal\Provider\AbstractProvider;
use Eram\Ersal\Request\BookingRequest;
use Eram\Ersal\Request\LabelResponse;
use Eram\Ersal\Request\PickupRequest;
use Eram\Ersal\Request\Quote;
use Eram\Ersal\Request\QuoteRequest;
use Eram\Ersal\Shipment\Shipment;
use Eram\Ersal\Shipment\ShipmentId;
use Eram\Ersal\Shipment\ShipmentStatus;
use Eram\Ersal\Tracking\TrackingEvent;

/**
 * Tipax (تیپاکس) shipping provider — REST API.
 *
 * Endpoint paths and field names follow Tipax's public developer portal
 * conventions. Verify against the current documentation before production
 * use — Tipax updates their API periodically.
 *
 * Capabilities: quote, book, track, cancel, label, pickup, branches, COD.
 */
final class TipaxProvider extends AbstractProvider implements
    ShippingInterface,
    SupportsLabel,
    SupportsPickup,
    SupportsBranches,
    SupportsCOD
{
    private const API_URL = 'https://api.tipax.ir/v1';
    private const SANDBOX_API_URL = 'https://sandbox.tipax.ir/v1';

    public function __construct(
        private readonly TipaxConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'tipax';
    }

    public function quote(QuoteRequest $request): array
    {
        $response = $this->postJson(
            $this->url('/shipments/quote'),
            [
                'origin' => $this->serializeAddress($request->origin),
                'destination' => $this->serializeAddress($request->destination),
                'parcel' => $this->serializeParcel($request->parcel),
                'service_level' => $request->serviceLevel,
                'cod_amount' => $request->codAmount?->inRials(),
            ],
            $this->authHeaders(),
        );

        $this->assertOk($response, 'quote');

        /** @var list<array<string, mixed>> $items */
        $items = $response['data']['quotes'] ?? [];

        $quotes = [];
        foreach ($items as $item) {
            $quotes[] = new Quote(
                providerName: $this->getName(),
                serviceLevel: (string) ($item['service_level'] ?? 'standard'),
                cost: Amount::fromRials((int) ($item['cost'] ?? 0)),
                etaDays: isset($item['eta_days']) ? (int) $item['eta_days'] : null,
                quoteId: $this->nullIfEmpty((string) ($item['quote_id'] ?? '')),
                extra: $item,
            );
        }

        $this->dispatch(new ShipmentQuoted($this->getName(), $request, $quotes));

        return $quotes;
    }

    public function createShipment(BookingRequest $request): ShipmentInterface
    {
        $response = $this->postJson(
            $this->url('/shipments'),
            [
                'order_id' => $request->orderId,
                'origin' => $this->serializeAddress($request->origin),
                'destination' => $this->serializeAddress($request->destination),
                'parcel' => $this->serializeParcel($request->parcel),
                'service_level' => $request->serviceLevel,
                'quote_id' => $request->quoteId,
                'cod_amount' => $request->codAmount?->inRials(),
                'description' => $request->description,
            ],
            $this->authHeaders(),
        );

        $this->assertOk($response, 'book');

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];

        $shipment = new Shipment(
            id: new ShipmentId((string) ($data['shipment_id'] ?? '')),
            providerName: $this->getName(),
            trackingCode: (string) ($data['tracking_code'] ?? ''),
            status: $this->mapStatus((string) ($data['status'] ?? 'booked')),
            origin: $request->origin,
            destination: $request->destination,
            parcel: $request->parcel,
            cost: isset($data['cost']) ? Amount::fromRials((int) $data['cost']) : null,
            extra: $data,
        );

        $this->dispatch(new ShipmentCreated($this->getName(), $shipment));

        return $shipment;
    }

    public function track(ShipmentId $id): ShipmentInterface
    {
        $response = $this->getJson(
            $this->url('/shipments/' . rawurlencode($id->value())),
            $this->authHeaders(),
        );

        $this->assertOk($response, 'track');

        return $this->hydrateShipment($id, $response['data'] ?? [], track: true);
    }

    public function cancel(ShipmentId $id): ShipmentInterface
    {
        $response = $this->deleteJson(
            $this->url('/shipments/' . rawurlencode($id->value())),
            $this->authHeaders(),
        );

        $this->assertOk($response, 'cancel');

        $shipment = $this->hydrateShipment($id, $response['data'] ?? [], track: false)
            ->withStatus(ShipmentStatus::Cancelled);

        $this->dispatch(new ShipmentCancelled($this->getName(), $shipment));

        return $shipment;
    }

    public function getLabel(ShipmentId $id): LabelResponse
    {
        $response = $this->getJson(
            $this->url('/shipments/' . rawurlencode($id->value()) . '/label'),
            $this->authHeaders(),
        );

        $this->assertOk($response, 'label');

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];

        $base64 = (string) ($data['bytes_base64'] ?? '');
        $bytes = $base64 !== '' ? (string) base64_decode($base64, true) : '';

        return new LabelResponse(
            format: (string) ($data['format'] ?? 'pdf'),
            bytes: $bytes,
            url: $this->nullIfEmpty((string) ($data['url'] ?? '')),
        );
    }

    public function schedulePickup(ShipmentId $id, PickupRequest $request): ShipmentInterface
    {
        $response = $this->postJson(
            $this->url('/shipments/' . rawurlencode($id->value()) . '/pickup'),
            [
                'window_start' => $request->windowStart->format(\DATE_ATOM),
                'window_end' => $request->windowEnd->format(\DATE_ATOM),
                'instructions' => $request->instructions,
            ],
            $this->authHeaders(),
        );

        $this->assertOk($response, 'pickup');

        return $this->hydrateShipment($id, $response['data'] ?? [], track: true);
    }

    public function listBranches(?string $city = null): array
    {
        $query = $city !== null ? '?city=' . rawurlencode($city) : '';

        $response = $this->getJson(
            $this->url('/branches' . $query),
            $this->authHeaders(),
        );

        $this->assertOk($response, 'quote');

        /** @var list<array<string, mixed>> $items */
        $items = $response['data']['branches'] ?? [];

        $branches = [];
        foreach ($items as $item) {
            $branches[] = new Branch(
                id: (string) ($item['id'] ?? ''),
                name: (string) ($item['name'] ?? ''),
                city: (string) ($item['city'] ?? ''),
                address: (string) ($item['address'] ?? ''),
                phone: $this->nullIfEmpty((string) ($item['phone'] ?? '')),
                lat: isset($item['lat']) ? (float) $item['lat'] : null,
                lng: isset($item['lng']) ? (float) $item['lng'] : null,
                openingHours: $this->nullIfEmpty((string) ($item['opening_hours'] ?? '')),
            );
        }

        return $branches;
    }

    private function url(string $path): string
    {
        $base = $this->config->baseUrl
            ?? ($this->config->sandbox ? self::SANDBOX_API_URL : self::API_URL);

        return rtrim($base, '/') . $path;
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->token,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAddress(Address $address): array
    {
        return [
            'first_name' => $address->firstName,
            'last_name' => $address->lastName,
            'phone' => $address->phone,
            'province' => $address->province,
            'city' => $address->city,
            'address_line' => $address->addressLine,
            'postal_code' => $address->postalCode,
            'plate' => $address->plate,
            'unit' => $address->unit,
            'lat' => $address->lat,
            'lng' => $address->lng,
            'national_id' => $address->nationalId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeParcel(Parcel $parcel): array
    {
        return [
            'weight_grams' => $parcel->weightGrams,
            'length_mm' => $parcel->lengthMm,
            'width_mm' => $parcel->widthMm,
            'height_mm' => $parcel->heightMm,
            'declared_value' => $parcel->declaredValue?->inRials(),
            'contents' => $parcel->contentsDescription,
            'fragile' => $parcel->fragile,
        ];
    }

    /**
     * @param array<string, mixed> $response
     */
    private function assertOk(array $response, string $operation): void
    {
        if (!isset($response['error'])) {
            return;
        }

        /** @var array<string, mixed> $error */
        $error = $response['error'];
        $code = (int) ($error['code'] ?? 1999);
        $errorEnum = TipaxErrorCode::tryFrom($code);
        $message = $errorEnum?->message() ?? (string) ($error['message'] ?? "Unknown error code: {$code}");

        match ($operation) {
            'book' => $this->failBooking($message, $code),
            'track' => $this->failTracking($message, $code),
            'cancel' => $this->failCancellation($message, $code),
            default => $this->failQuote($message, $code),
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrateShipment(ShipmentId $id, array $data, bool $track): ShipmentInterface
    {
        $origin = isset($data['origin']) && \is_array($data['origin'])
            ? $this->deserializeAddress($data['origin'])
            : $this->placeholderAddress();
        $destination = isset($data['destination']) && \is_array($data['destination'])
            ? $this->deserializeAddress($data['destination'])
            : $this->placeholderAddress();
        $parcel = isset($data['parcel']) && \is_array($data['parcel'])
            ? $this->deserializeParcel($data['parcel'])
            : new Parcel(weightGrams: 1);

        $history = [];
        /** @var list<array<string, mixed>> $events */
        $events = $data['history'] ?? [];
        foreach ($events as $event) {
            $history[] = new TrackingEvent(
                at: new \DateTimeImmutable((string) ($event['at'] ?? 'now')),
                status: $this->mapStatus((string) ($event['status'] ?? 'in_transit')),
                description: (string) ($event['description'] ?? ''),
                location: $this->nullIfEmpty((string) ($event['location'] ?? '')),
                raw: $event,
            );
        }

        $shipment = new Shipment(
            id: $id,
            providerName: $this->getName(),
            trackingCode: (string) ($data['tracking_code'] ?? ''),
            status: $this->mapStatus((string) ($data['status'] ?? 'booked')),
            origin: $origin,
            destination: $destination,
            parcel: $parcel,
            cost: isset($data['cost']) ? Amount::fromRials((int) $data['cost']) : null,
            history: $history,
            extra: $data,
        );

        if ($track) {
            $this->dispatch(new ShipmentTracked($this->getName(), $shipment));
        }

        return $shipment;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function deserializeAddress(array $data): Address
    {
        return new Address(
            firstName: (string) ($data['first_name'] ?? '-'),
            lastName: (string) ($data['last_name'] ?? '-'),
            phone: (string) ($data['phone'] ?? '09000000000'),
            province: (string) ($data['province'] ?? '-'),
            city: (string) ($data['city'] ?? '-'),
            addressLine: (string) ($data['address_line'] ?? '-'),
            postalCode: isset($data['postal_code']) && $data['postal_code'] !== null
                ? (string) $data['postal_code']
                : null,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function deserializeParcel(array $data): Parcel
    {
        return new Parcel(
            weightGrams: max(1, (int) ($data['weight_grams'] ?? 1)),
            lengthMm: isset($data['length_mm']) ? (int) $data['length_mm'] : null,
            widthMm: isset($data['width_mm']) ? (int) $data['width_mm'] : null,
            heightMm: isset($data['height_mm']) ? (int) $data['height_mm'] : null,
        );
    }

    private function placeholderAddress(): Address
    {
        return new Address(
            firstName: '-',
            lastName: '-',
            phone: '09000000000',
            province: '-',
            city: '-',
            addressLine: '-',
        );
    }

    private function mapStatus(string $raw): ShipmentStatus
    {
        return match (strtolower($raw)) {
            'draft' => ShipmentStatus::Draft,
            'quoted' => ShipmentStatus::Quoted,
            'booked', 'accepted', 'pending' => ShipmentStatus::Booked,
            'picked_up', 'collected' => ShipmentStatus::PickedUp,
            'in_transit', 'shipping' => ShipmentStatus::InTransit,
            'out_for_delivery', 'delivering' => ShipmentStatus::OutForDelivery,
            'delivered' => ShipmentStatus::Delivered,
            'failed', 'undeliverable' => ShipmentStatus::Failed,
            'returned' => ShipmentStatus::Returned,
            'cancelled', 'canceled' => ShipmentStatus::Cancelled,
            default => ShipmentStatus::InTransit,
        };
    }
}
