<?php

declare(strict_types=1);

namespace Eram\Ersal\Event;

use Eram\Ersal\Contracts\ShipmentInterface;

final class ShipmentTracked
{
    public function __construct(
        public string $providerName,
        public ShipmentInterface $shipment,
    ) {}
}
