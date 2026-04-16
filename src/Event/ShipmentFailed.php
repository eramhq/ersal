<?php

declare(strict_types=1);

namespace Eram\Ersal\Event;

final class ShipmentFailed
{
    /**
     * @param 'quote'|'book'|'track'|'cancel'|'label'|'pickup' $operation
     */
    public function __construct(
        public string $providerName,
        public string $operation,
        public string $reason,
        public int|string $errorCode = 0,
    ) {}
}
