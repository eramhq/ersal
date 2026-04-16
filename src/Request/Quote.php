<?php

declare(strict_types=1);

namespace Eram\Ersal\Request;

use Eram\Ersal\Money\Amount;

/**
 * A single priced quote returned from ShippingInterface::quote().
 *
 * `quoteId` — when non-null — can be passed back via BookingRequest::$quoteId
 * to re-use this exact price on a subsequent booking. Carriers that don't
 * issue quote tokens leave this null.
 */
final class Quote
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public readonly string $providerName,
        public readonly string $serviceLevel,
        public readonly Amount $cost,
        public readonly ?int $etaDays = null,
        public readonly ?string $quoteId = null,
        public readonly array $extra = [],
    ) {}
}
