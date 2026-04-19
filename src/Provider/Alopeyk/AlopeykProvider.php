<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Alopeyk;

use Eram\Ersal\Contracts\ShipmentInterface;
use Eram\Ersal\Contracts\ShippingInterface;
use Eram\Ersal\Contracts\SupportsPickup;
use Eram\Ersal\Event\ShipmentCancelled;
use Eram\Ersal\Event\ShipmentCreated;
use Eram\Ersal\Event\ShipmentQuoted;
use Eram\Ersal\Event\ShipmentTracked;
use Eram\Ersal\Http\EventDispatcher;
use Eram\Ersal\Http\HttpClient;
use Eram\Ersal\Http\Logger;
use Eram\Abzar\Money\Amount;
use Eram\Ersal\Provider\AbstractProvider;
use Eram\Ersal\Request\BookingRequest;
use Eram\Ersal\Request\PickupRequest;
use Eram\Ersal\Request\Quote;
use Eram\Ersal\Request\QuoteRequest;
use Eram\Ersal\Shipment\Shipment;
use Eram\Ersal\Shipment\ShipmentId;
use Eram\Ersal\Shipment\ShipmentStatus;

/**
 * Alopeyk (الوپیک) on-demand urban delivery — REST API.
 *
 * Capabilities: quote, book, track, cancel, pickup.
 * No COD and no printed labels (urban same-day model).
 */
final class AlopeykProvider extends AbstractProvider implements
    ShippingInterface,
    SupportsPickup
{
    private const API_URL = 'https://api.alopeyk.com/v1';
    private const SANDBOX_API_URL = 'https://sandbox.alopeyk.com/v1';

    public function __construct(
        private readonly AlopeykConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'alopeyk';
    }

    public function quote(QuoteRequest $request): array
    {
        $response = $this->postJson($this->url('/price-inquiry'), [
            'origin' => $this->serializeAddress($request->origin),
            'destination' => $this->serializeAddress($request->destination),
            'parcel' => $this->serializeParcel($request->parcel),
            'service_level' => $request->serviceLevel,
        ], $this->authHeaders());

        $this->assertOk($response, 'quote');

        /** @var list<array<string, mixed>> $items */
        $items = $response['data']['quotes'] ?? [];

        $quotes = [];
        foreach ($items as $item) {
            $quotes[] = new Quote(
                providerName: $this->getName(),
                serviceLevel: (string) ($item['service_level'] ?? 'same_day'),
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
        $response = $this->postJson($this->url('/orders'), [
            'order_id' => $request->orderId,
            'quote_id' => $request->quoteId,
            'service_level' => $request->serviceLevel,
            'origin' => $this->serializeAddress($request->origin),
            'destination' => $this->serializeAddress($request->destination),
            'parcel' => $this->serializeParcel($request->parcel),
            'description' => $request->description,
        ], $this->authHeaders());

        $this->assertOk($response, 'book');

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];

        $shipment = new Shipment(
            id: new ShipmentId((string) ($data['order_id'] ?? '')),
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
        $response = $this->getJson($this->url('/orders/' . rawurlencode($id->value())), $this->authHeaders());
        $this->assertOk($response, 'track');

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];
        $shipment = $this->hydrateShipment($id, $data);

        $this->dispatch(new ShipmentTracked($this->getName(), $shipment));

        return $shipment;
    }

    public function cancel(ShipmentId $id): ShipmentInterface
    {
        $response = $this->deleteJson($this->url('/orders/' . rawurlencode($id->value())), $this->authHeaders());
        $this->assertOk($response, 'cancel');

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];
        $shipment = $this->hydrateShipment($id, $data)->withStatus(ShipmentStatus::Cancelled);
        $this->dispatch(new ShipmentCancelled($this->getName(), $shipment));

        return $shipment;
    }

    public function schedulePickup(ShipmentId $id, PickupRequest $request): ShipmentInterface
    {
        $response = $this->postJson(
            $this->url('/orders/' . rawurlencode($id->value()) . '/pickup'),
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
        return AlopeykErrorCode::tryFrom($code)?->message();
    }

    protected function defaultErrorCode(): int
    {
        return AlopeykErrorCode::InternalError->value;
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
        return ['Authorization' => 'Bearer ' . $this->config->token];
    }
}
