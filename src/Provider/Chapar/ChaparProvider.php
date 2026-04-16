<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Chapar;

use Eram\Ersal\Contracts\ShipmentInterface;
use Eram\Ersal\Contracts\ShippingInterface;
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

/**
 * Chapar Express (چاپار) shipping provider — REST API.
 *
 * Capabilities: quote, book, track, cancel, label, pickup, COD.
 */
final class ChaparProvider extends AbstractProvider implements
    ShippingInterface,
    SupportsLabel,
    SupportsPickup,
    SupportsCOD
{
    private const API_URL = 'https://api.chapar.com/v1';
    private const SANDBOX_API_URL = 'https://sandbox.chapar.com/v1';

    public function __construct(
        private readonly ChaparConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'chapar';
    }

    public function quote(QuoteRequest $request): array
    {
        $response = $this->postJson(
            $this->url('/rates'),
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
        $items = $response['data']['rates'] ?? [];

        $quotes = [];
        foreach ($items as $item) {
            $quotes[] = new Quote(
                providerName: $this->getName(),
                serviceLevel: (string) ($item['service_level'] ?? 'standard'),
                cost: Amount::fromRials((int) ($item['cost'] ?? 0)),
                etaDays: isset($item['eta_days']) ? (int) $item['eta_days'] : null,
                quoteId: $this->nullIfEmpty((string) ($item['rate_id'] ?? '')),
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
                'rate_id' => $request->quoteId,
                'service_level' => $request->serviceLevel,
                'origin' => $this->serializeAddress($request->origin),
                'destination' => $this->serializeAddress($request->destination),
                'parcel' => $this->serializeParcel($request->parcel),
                'cod_amount' => $request->codAmount?->inRials(),
                'description' => $request->description,
            ],
            $this->authHeaders(),
        );

        $this->assertOk($response, 'book');

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];

        $shipment = new Shipment(
            id: new ShipmentId((string) ($data['id'] ?? '')),
            providerName: $this->getName(),
            trackingCode: (string) ($data['tracking_code'] ?? ''),
            status: ShipmentStatus::fromCanonical((string) ($data['status'] ?? 'booked')),
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

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];
        $shipment = $this->hydrateShipment($id, $data);

        $this->dispatch(new ShipmentTracked($this->getName(), $shipment));

        return $shipment;
    }

    public function cancel(ShipmentId $id): ShipmentInterface
    {
        $response = $this->deleteJson(
            $this->url('/shipments/' . rawurlencode($id->value())),
            $this->authHeaders(),
        );

        $this->assertOk($response, 'cancel');

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];
        $shipment = $this->hydrateShipment($id, $data)->withStatus(ShipmentStatus::Cancelled);

        $this->dispatch(new ShipmentCancelled($this->getName(), $shipment));

        return $shipment;
    }

    public function getLabel(ShipmentId $id): LabelResponse
    {
        $response = $this->getJson(
            $this->url('/shipments/' . rawurlencode($id->value()) . '/label'),
            $this->authHeaders(),
        );

        $this->assertOk($response, 'track');

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

        $this->assertOk($response, 'book');

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];

        return $this->hydrateShipment($id, $data);
    }

    protected function resolveErrorMessage(int $code): ?string
    {
        return ChaparErrorCode::tryFrom($code)?->message();
    }

    protected function defaultErrorCode(): int
    {
        return ChaparErrorCode::InternalError->value;
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
            'X-Api-Key' => $this->config->apiKey,
        ];
    }
}
