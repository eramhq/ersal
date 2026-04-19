<?php

declare(strict_types=1);

namespace Eram\Ersal\Contracts;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Abzar\Money\Amount;
use Eram\Ersal\Shipment\ShipmentId;
use Eram\Ersal\Shipment\ShipmentStatus;
use Eram\Ersal\Tracking\TrackingEvent;

interface ShipmentInterface
{
    public function getId(): ShipmentId;

    public function getProviderName(): string;

    /**
     * Carrier-issued human-facing tracking code (shown to end users).
     */
    public function getTrackingCode(): string;

    public function getStatus(): ShipmentStatus;

    public function getOrigin(): Address;

    public function getDestination(): Address;

    public function getParcel(): Parcel;

    /**
     * Priced cost of the shipment, if known. Null until a quote has been
     * returned or the shipment is booked.
     */
    public function getCost(): ?Amount;

    /**
     * Chronological tracking history, oldest first.
     *
     * @return list<TrackingEvent>
     */
    public function getHistory(): array;

    /**
     * Any extra data returned by the carrier that doesn't fit the normalized shape.
     *
     * @return array<string, mixed>
     */
    public function getExtra(): array;

    public function withStatus(ShipmentStatus $status): static;

    /**
     * @param list<TrackingEvent> $history
     */
    public function withHistory(array $history): static;
}
