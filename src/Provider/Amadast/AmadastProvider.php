<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Amadast;

use Eram\Ersal\Contracts\ShipmentInterface;
use Eram\Ersal\Contracts\ShippingInterface;
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

/**
 * Amadast (آمادست) cross-carrier aggregator — REST API.
 *
 * Acts as a broker across multiple underlying carriers. Capabilities:
 * quote, book, track, cancel. Label/pickup/COD are handled by the
 * underlying carrier Amadast routes to, not Amadast itself.
 */
final class AmadastProvider extends AbstractProvider implements ShippingInterface
{
    private const API_URL = 'https://api.amadast.com/v1';
    private const SANDBOX_API_URL = 'https://sandbox.amadast.com/v1';

    public function __construct(
        private readonly AmadastConfig $config,
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        parent::__construct($httpClient, $logger, $eventDispatcher);
    }

    public function getName(): string
    {
        return 'amadast';
    }

    public function quote(QuoteRequest $request): array
    {
        $response = $this->postJson($this->url('/quotes'), [
            'origin' => $this->serializeAddress($request->origin),
            'destination' => $this->serializeAddress($request->destination),
            'parcel' => $this->serializeParcel($request->parcel),
            'service_level' => $request->serviceLevel,
        ], $this->authHeaders());

        $this->assertOk($response, 'quote');

        /** @var list<array<string, mixed>> $items */
        $items = $response['data']['offers'] ?? [];

        $quotes = [];
        foreach ($items as $item) {
            $quotes[] = new Quote(
                providerName: $this->getName(),
                serviceLevel: (string) ($item['service_level'] ?? 'standard'),
                cost: Amount::fromRials((int) ($item['cost'] ?? 0)),
                etaDays: isset($item['eta_days']) ? (int) $item['eta_days'] : null,
                quoteId: $this->nullIfEmpty((string) ($item['offer_id'] ?? '')),
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
            'offer_id' => $request->quoteId,
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
        $response = $this->getJson($this->url('/shipments/' . rawurlencode($id->value())), $this->authHeaders());
        $this->assertOk($response, 'track');

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];
        $shipment = $this->hydrateShipment($id, $data);

        $this->dispatch(new ShipmentTracked($this->getName(), $shipment));

        return $shipment;
    }

    public function cancel(ShipmentId $id): ShipmentInterface
    {
        $response = $this->deleteJson($this->url('/shipments/' . rawurlencode($id->value())), $this->authHeaders());
        $this->assertOk($response, 'cancel');

        /** @var array<string, mixed> $data */
        $data = $response['data'] ?? [];
        $shipment = $this->hydrateShipment($id, $data)->withStatus(ShipmentStatus::Cancelled);
        $this->dispatch(new ShipmentCancelled($this->getName(), $shipment));

        return $shipment;
    }

    protected function resolveErrorMessage(int $code): ?string
    {
        return AmadastErrorCode::tryFrom($code)?->message();
    }

    protected function defaultErrorCode(): int
    {
        return AmadastErrorCode::InternalError->value;
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
}
