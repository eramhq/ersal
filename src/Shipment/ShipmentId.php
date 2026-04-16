<?php

declare(strict_types=1);

namespace Eram\Ersal\Shipment;

/**
 * Value object wrapping a provider-assigned shipment identifier.
 *
 * Distinct from the merchant's own order ID and from the human-facing
 * tracking code — this is the opaque handle used to reference a shipment
 * in subsequent track() and cancel() calls.
 */
final class ShipmentId
{
    public function __construct(
        private string $value,
    ) {}

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
