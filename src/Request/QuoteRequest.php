<?php

declare(strict_types=1);

namespace Eram\Ersal\Request;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Money\Amount;

/**
 * Immutable DTO for quoting a shipment without booking it.
 *
 * A single QuoteRequest can produce multiple Quote results from a carrier
 * (one per service level, e.g. express + standard).
 */
final class QuoteRequest
{
    /**
     * @param array<string, mixed> $extra Provider-specific extra parameters.
     */
    public function __construct(
        public readonly Address $origin,
        public readonly Address $destination,
        public readonly Parcel $parcel,
        public readonly ?string $serviceLevel = null,
        public readonly ?Amount $codAmount = null,
        public readonly array $extra = [],
    ) {}

    public function hasCashOnDelivery(): bool
    {
        return $this->codAmount !== null && !$this->codAmount->isZero();
    }
}
