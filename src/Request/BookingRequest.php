<?php

declare(strict_types=1);

namespace Eram\Ersal\Request;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Abzar\Money\Amount;

/**
 * Immutable DTO for creating (booking) a shipment with a carrier.
 *
 * `orderId` is the merchant's own reference — echoed back in events and
 * useful for reconciliation. `quoteId` (optional) reuses a price returned
 * from a prior quote() call on carriers that support it.
 */
final class BookingRequest
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public readonly Address $origin,
        public readonly Address $destination,
        public readonly Parcel $parcel,
        public readonly string $orderId,
        public readonly ?string $serviceLevel = null,
        public readonly ?string $quoteId = null,
        public readonly ?Amount $codAmount = null,
        public readonly ?string $description = null,
        public readonly array $extra = [],
    ) {}

    public function hasCashOnDelivery(): bool
    {
        return $this->codAmount !== null && !$this->codAmount->isZero();
    }

    public function withCashOnDelivery(Amount $amount): self
    {
        return new self(
            $this->origin,
            $this->destination,
            $this->parcel,
            $this->orderId,
            $this->serviceLevel,
            $this->quoteId,
            $amount,
            $this->description,
            $this->extra,
        );
    }
}
