<?php

declare(strict_types=1);

namespace Eram\Ersal\Request;

/**
 * Immutable DTO for scheduling a pickup from the sender's address.
 *
 * `windowStart` and `windowEnd` bracket a time slot in the local timezone;
 * most Iranian carriers expose ~2-hour windows during business hours.
 */
final class PickupRequest
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public readonly \DateTimeImmutable $windowStart,
        public readonly \DateTimeImmutable $windowEnd,
        public readonly ?string $instructions = null,
        public readonly array $extra = [],
    ) {}
}
