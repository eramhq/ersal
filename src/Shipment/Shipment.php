<?php

declare(strict_types=1);

namespace Eram\Ersal\Shipment;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Contracts\ShipmentInterface;
use Eram\Ersal\Money\Amount;
use Eram\Ersal\Tracking\TrackingEvent;

/**
 * Immutable snapshot of a shipment at a point in time.
 *
 * Mutations (`withStatus`, `withHistory`, `withCost`) return new instances —
 * the original is never modified.
 */
final class Shipment implements ShipmentInterface
{
    /**
     * @param list<TrackingEvent> $history
     * @param array<string, mixed> $extra
     */
    public function __construct(
        private ShipmentId $id,
        private string $providerName,
        private string $trackingCode,
        private ShipmentStatus $status,
        private Address $origin,
        private Address $destination,
        private Parcel $parcel,
        private ?Amount $cost = null,
        private array $history = [],
        private array $extra = [],
    ) {}

    public function getId(): ShipmentId
    {
        return $this->id;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function getTrackingCode(): string
    {
        return $this->trackingCode;
    }

    public function getStatus(): ShipmentStatus
    {
        return $this->status;
    }

    public function getOrigin(): Address
    {
        return $this->origin;
    }

    public function getDestination(): Address
    {
        return $this->destination;
    }

    public function getParcel(): Parcel
    {
        return $this->parcel;
    }

    public function getCost(): ?Amount
    {
        return $this->cost;
    }

    /**
     * @return list<TrackingEvent>
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    public function latestEvent(): ?TrackingEvent
    {
        $count = \count($this->history);

        return $count === 0 ? null : $this->history[$count - 1];
    }

    public function withStatus(ShipmentStatus $status): static
    {
        return new self(
            $this->id,
            $this->providerName,
            $this->trackingCode,
            $status,
            $this->origin,
            $this->destination,
            $this->parcel,
            $this->cost,
            $this->history,
            $this->extra,
        );
    }

    /**
     * @param list<TrackingEvent> $history
     */
    public function withHistory(array $history): static
    {
        return new self(
            $this->id,
            $this->providerName,
            $this->trackingCode,
            $this->status,
            $this->origin,
            $this->destination,
            $this->parcel,
            $this->cost,
            $history,
            $this->extra,
        );
    }

    public function withCost(Amount $cost): self
    {
        return new self(
            $this->id,
            $this->providerName,
            $this->trackingCode,
            $this->status,
            $this->origin,
            $this->destination,
            $this->parcel,
            $cost,
            $this->history,
            $this->extra,
        );
    }
}
