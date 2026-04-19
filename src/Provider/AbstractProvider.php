<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider;

use Eram\Abzar\Money\Amount;
use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Contracts\ShipmentInterface;
use Eram\Ersal\Contracts\ShippingInterface;
use Eram\Ersal\Exception\ConnectionException;
use Eram\Ersal\Http\EventDispatcher;
use Eram\Ersal\Http\HttpClient;
use Eram\Ersal\Http\HttpResponse;
use Eram\Ersal\Http\Logger;
use Eram\Ersal\Http\NullLogger;
use Eram\Ersal\Shipment\Shipment;
use Eram\Ersal\Shipment\ShipmentId;
use Eram\Ersal\Shipment\ShipmentStatus;
use Eram\Ersal\Tracking\TrackingEvent;

/**
 * Base class for REST-based shipping providers.
 *
 * Centralizes the shared REST payload shape used by every Iranian carrier
 * we support: wire-level address/parcel serialization, shipment hydration
 * from a canonical response envelope, and carrier-agnostic error dispatch.
 */
abstract class AbstractProvider implements ShippingInterface
{
    use ProviderHelperTrait;

    protected HttpClient $httpClient;
    protected Logger $logger;

    public function __construct(
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger ?? new NullLogger();
        $this->eventDispatcher = $eventDispatcher;
    }

    abstract public function getName(): string;

    /**
     * Resolve a carrier error code into a human message.
     *
     * Each concrete provider returns its {Provider}ErrorCode::tryFrom(...)->message(), or null if unknown.
     */
    abstract protected function resolveErrorMessage(int $code): ?string;

    /**
     * Fallback error code used when the carrier response omits `error.code`.
     */
    abstract protected function defaultErrorCode(): int;

    /**
     * POST JSON to the carrier API and return the decoded response body.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @return array<string, mixed>
     * @throws ConnectionException On transport, encode, or decode failure.
     */
    protected function postJson(string $url, array $data, array $headers = []): array
    {
        try {
            $jsonBody = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw new ConnectionException("Failed to encode request body: {$e->getMessage()}", 0, $e);
        }

        $this->logger->debug('Ersal: POST', [
            'provider' => $this->getName(),
            'url' => $url,
        ]);

        return $this->decode($this->httpClient->postJson($url, $jsonBody, $headers), $url);
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>
     * @throws ConnectionException
     */
    protected function getJson(string $url, array $headers = []): array
    {
        $this->logger->debug('Ersal: GET', [
            'provider' => $this->getName(),
            'url' => $url,
        ]);

        return $this->decode($this->httpClient->getJson($url, $headers), $url);
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>
     * @throws ConnectionException
     */
    protected function deleteJson(string $url, array $headers = []): array
    {
        $this->logger->debug('Ersal: DELETE', [
            'provider' => $this->getName(),
            'url' => $url,
        ]);

        return $this->decode($this->httpClient->deleteJson($url, $headers), $url);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(HttpResponse $response, string $url): array
    {
        if ($response->body === '') {
            return [];
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ConnectionException(
                \sprintf('Failed to decode response from %s: %s', $url, $e->getMessage()),
                0,
                $e,
            );
        }

        return $decoded;
    }

    /**
     * Check a decoded carrier response for an `error` envelope and throw
     * the appropriate exception subclass if present.
     *
     * @param array<string, mixed> $response
     * @param 'quote'|'book'|'track'|'cancel' $operation
     */
    protected function assertOk(array $response, string $operation): void
    {
        if (!isset($response['error'])) {
            return;
        }

        /** @var array<string, mixed> $error */
        $error = $response['error'];
        $code = (int) ($error['code'] ?? $this->defaultErrorCode());
        $message = $this->resolveErrorMessage($code)
            ?? (string) ($error['message'] ?? "Unknown error code: {$code}");

        match ($operation) {
            'book' => $this->failBooking($message, $code),
            'track' => $this->failTracking($message, $code),
            'cancel' => $this->failCancellation($message, $code),
            'quote' => $this->failQuote($message, $code),
        };
    }

    /**
     * Canonical wire serialization of an Address for REST providers.
     *
     * Carriers that don't consume every field simply ignore unknown keys.
     *
     * @return array<string, mixed>
     */
    protected function serializeAddress(Address $a): array
    {
        return [
            'first_name' => $a->firstName,
            'last_name' => $a->lastName,
            'phone' => $a->phone,
            'province' => $a->province,
            'city' => $a->city,
            'address_line' => $a->addressLine,
            'postal_code' => $a->postalCode,
            'plate' => $a->plate,
            'unit' => $a->unit,
            'lat' => $a->lat,
            'lng' => $a->lng,
            'national_id' => $a->nationalId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeParcel(Parcel $p): array
    {
        return [
            'weight_grams' => $p->weightGrams,
            'length_mm' => $p->lengthMm,
            'width_mm' => $p->widthMm,
            'height_mm' => $p->heightMm,
            'declared_value' => $p->declaredValue?->inRials(),
            'contents' => $p->contentsDescription,
            'fragile' => $p->fragile,
        ];
    }

    /**
     * Parse a carrier-returned address block back into an Address.
     *
     * Addresses returned by carriers frequently omit fields. Missing
     * values are filled with sentinels (`-` / `+989000000000`) so that
     * `Address`'s own validation still passes — callers should treat
     * such reconstructed addresses as opaque, not re-usable input.
     *
     * @param array<string, mixed> $d
     */
    protected function deserializeAddress(array $d): Address
    {
        return new Address(
            firstName: (string) ($d['first_name'] ?? '-'),
            lastName: (string) ($d['last_name'] ?? '-'),
            phone: (string) ($d['phone'] ?? '09000000000'),
            province: (string) ($d['province'] ?? '-'),
            city: (string) ($d['city'] ?? '-'),
            addressLine: (string) ($d['address_line'] ?? '-'),
            postalCode: isset($d['postal_code']) ? (string) $d['postal_code'] : null,
        );
    }

    /**
     * @param array<string, mixed> $d
     */
    protected function deserializeParcel(array $d): Parcel
    {
        return new Parcel(
            weightGrams: max(1, (int) ($d['weight_grams'] ?? 1)),
            lengthMm: isset($d['length_mm']) ? (int) $d['length_mm'] : null,
            widthMm: isset($d['width_mm']) ? (int) $d['width_mm'] : null,
            heightMm: isset($d['height_mm']) ? (int) $d['height_mm'] : null,
        );
    }

    /**
     * Placeholder address used when the carrier response doesn't echo
     * origin/destination back (common on `cancel()` endpoints).
     */
    protected function placeholderAddress(): Address
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

    /**
     * Build a Shipment from a canonical response envelope.
     *
     * The envelope is the `data` portion of a REST response and may contain:
     *   - `tracking_code`: string
     *   - `status`: string (mapped via ShipmentStatus::fromCanonical)
     *   - `cost`: int (rials)
     *   - `origin`/`destination`: address arrays (optional)
     *   - `parcel`: parcel array (optional)
     *   - `history`: array of tracking events (optional)
     *
     * The caller decides which lifecycle event (if any) to dispatch.
     *
     * @param array<string, mixed> $data
     */
    protected function hydrateShipment(ShipmentId $id, array $data): ShipmentInterface
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
                status: ShipmentStatus::fromCanonical((string) ($event['status'] ?? 'in_transit')),
                description: (string) ($event['description'] ?? ''),
                location: $this->nullIfEmpty((string) ($event['location'] ?? '')),
                raw: $event,
            );
        }

        // Drop the history array before storing the rest as `extra` — otherwise
        // we hold each event twice (once in TrackingEvent::$raw, once in Shipment::$extra).
        unset($data['history']);

        return new Shipment(
            id: $id,
            providerName: $this->getName(),
            trackingCode: (string) ($data['tracking_code'] ?? ''),
            status: ShipmentStatus::fromCanonical((string) ($data['status'] ?? 'booked')),
            origin: $origin,
            destination: $destination,
            parcel: $parcel,
            cost: isset($data['cost']) ? Amount::fromRials((int) $data['cost']) : null,
            history: $history,
            extra: $data,
        );
    }
}
