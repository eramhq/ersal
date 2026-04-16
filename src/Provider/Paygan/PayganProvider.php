<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Paygan;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Contracts\ShipmentInterface;
use Eram\Ersal\Contracts\ShippingInterface;
use Eram\Ersal\Contracts\SupportsCOD;
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
use Eram\Ersal\Request\Quote;
use Eram\Ersal\Request\QuoteRequest;
use Eram\Ersal\Shipment\Shipment;
use Eram\Ersal\Shipment\ShipmentId;
use Eram\Ersal\Shipment\ShipmentStatus;
use Eram\Ersal\Tracking\TrackingEvent;

/**
 * Paygan (پایگان) shipping provider — REST API.
 *
 * Capabilities: quote, book, track, cancel, COD.
 */
final class PayganProvider extends AbstractProvider implements
    ShippingInterface,
    SupportsCOD
{
    private const API_URL = 'https://api.paygan.io/v1';
    private const SANDBOX_API_URL = 'https://sandbox.paygan.io/v1';

    public function __construct(
        private readonly PayganConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'paygan';
    }

    public function quote(QuoteRequest $request): array
    {
        $response = $this->postJson($this->url('/quote'), [
            'origin' => $this->serializeAddress($request->origin),
            'destination' => $this->serializeAddress($request->destination),
            'parcel' => $this->serializeParcel($request->parcel),
            'service_level' => $request->serviceLevel,
            'cod_amount' => $request->codAmount?->inRials(),
        ], $this->authHeaders());

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
        $response = $this->postJson($this->url('/shipments'), [
            'order_id' => $request->orderId,
            'quote_id' => $request->quoteId,
            'service_level' => $request->serviceLevel,
            'origin' => $this->serializeAddress($request->origin),
            'destination' => $this->serializeAddress($request->destination),
            'parcel' => $this->serializeParcel($request->parcel),
            'cod_amount' => $request->codAmount?->inRials(),
            'description' => $request->description,
        ], $this->authHeaders());

        $this->assertOk($response, 'book');

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];

        $shipment = new Shipment(
            id: new ShipmentId((string) ($data['id'] ?? '')),
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
        $response = $this->getJson($this->url('/shipments/' . rawurlencode($id->value())), $this->authHeaders());
        $this->assertOk($response, 'track');

        return $this->hydrate($id, $response['data'] ?? [], track: true);
    }

    public function cancel(ShipmentId $id): ShipmentInterface
    {
        $response = $this->deleteJson($this->url('/shipments/' . rawurlencode($id->value())), $this->authHeaders());
        $this->assertOk($response, 'cancel');

        $shipment = $this->hydrate($id, $response['data'] ?? [], track: false)->withStatus(ShipmentStatus::Cancelled);

        $this->dispatch(new ShipmentCancelled($this->getName(), $shipment));

        return $shipment;
    }

    private function url(string $path): string
    {
        $base = $this->config->baseUrl ?? ($this->config->sandbox ? self::SANDBOX_API_URL : self::API_URL);

        return rtrim($base, '/') . $path;
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        return ['X-Api-Key' => $this->config->apiKey];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAddress(Address $a): array
    {
        return [
            'first_name' => $a->firstName, 'last_name' => $a->lastName, 'phone' => $a->phone,
            'province' => $a->province, 'city' => $a->city, 'address_line' => $a->addressLine,
            'postal_code' => $a->postalCode, 'plate' => $a->plate, 'unit' => $a->unit,
            'lat' => $a->lat, 'lng' => $a->lng, 'national_id' => $a->nationalId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeParcel(Parcel $p): array
    {
        return [
            'weight_grams' => $p->weightGrams, 'length_mm' => $p->lengthMm,
            'width_mm' => $p->widthMm, 'height_mm' => $p->heightMm,
            'declared_value' => $p->declaredValue?->inRials(),
            'contents' => $p->contentsDescription, 'fragile' => $p->fragile,
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
        $code = (int) ($error['code'] ?? 4999);
        $errorEnum = PayganErrorCode::tryFrom($code);
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
    private function hydrate(ShipmentId $id, array $data, bool $track): ShipmentInterface
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
     * @param array<string, mixed> $d
     */
    private function deserializeAddress(array $d): Address
    {
        return new Address(
            firstName: (string) ($d['first_name'] ?? '-'),
            lastName: (string) ($d['last_name'] ?? '-'),
            phone: (string) ($d['phone'] ?? '09000000000'),
            province: (string) ($d['province'] ?? '-'),
            city: (string) ($d['city'] ?? '-'),
            addressLine: (string) ($d['address_line'] ?? '-'),
            postalCode: isset($d['postal_code']) && $d['postal_code'] !== null ? (string) $d['postal_code'] : null,
        );
    }

    /**
     * @param array<string, mixed> $d
     */
    private function deserializeParcel(array $d): Parcel
    {
        return new Parcel(
            weightGrams: max(1, (int) ($d['weight_grams'] ?? 1)),
            lengthMm: isset($d['length_mm']) ? (int) $d['length_mm'] : null,
            widthMm: isset($d['width_mm']) ? (int) $d['width_mm'] : null,
            heightMm: isset($d['height_mm']) ? (int) $d['height_mm'] : null,
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
            'booked', 'accepted' => ShipmentStatus::Booked,
            'picked_up' => ShipmentStatus::PickedUp,
            'in_transit' => ShipmentStatus::InTransit,
            'out_for_delivery' => ShipmentStatus::OutForDelivery,
            'delivered' => ShipmentStatus::Delivered,
            'failed' => ShipmentStatus::Failed,
            'returned' => ShipmentStatus::Returned,
            'cancelled', 'canceled' => ShipmentStatus::Cancelled,
            default => ShipmentStatus::InTransit,
        };
    }
}
