<?php

declare(strict_types=1);

namespace Eram\Ersal\Event;

use Eram\Ersal\Request\Quote;
use Eram\Ersal\Request\QuoteRequest;

final class ShipmentQuoted
{
    /**
     * @param list<Quote> $quotes
     */
    public function __construct(
        public string $providerName,
        public QuoteRequest $request,
        public array $quotes,
    ) {}
}
