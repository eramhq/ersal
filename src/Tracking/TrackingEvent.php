<?php

declare(strict_types=1);

namespace Eram\Ersal\Tracking;

use Eram\Ersal\Shipment\ShipmentStatus;

/**
 * A single entry in a shipment's tracking history.
 *
 * The `raw` array holds the carrier-native event payload for debugging
 * and for recovering fields that don't fit the normalized shape.
 */
final class TrackingEvent
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly \DateTimeImmutable $at,
        public readonly ShipmentStatus $status,
        public readonly string $description,
        public readonly ?string $location = null,
        public readonly array $raw = [],
    ) {}
}
