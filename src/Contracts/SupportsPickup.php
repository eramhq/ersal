<?php

declare(strict_types=1);

namespace Eram\Ersal\Contracts;

use Eram\Ersal\Request\PickupRequest;
use Eram\Ersal\Shipment\ShipmentId;

/**
 * Implemented by providers that can schedule a pickup from the sender's address.
 */
interface SupportsPickup
{
    public function schedulePickup(ShipmentId $id, PickupRequest $request): ShipmentInterface;
}
