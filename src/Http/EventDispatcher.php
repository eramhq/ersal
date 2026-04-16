<?php

declare(strict_types=1);

namespace Eram\Ersal\Http;

/**
 * Minimal event dispatcher contract.
 *
 * Implementations receive shipment lifecycle events (ShipmentQuoted,
 * ShipmentCreated, ShipmentTracked, ShipmentCancelled, ShipmentFailed)
 * and can dispatch them to registered listeners.
 */
interface EventDispatcher
{
    /**
     * Dispatch an event. Returns the event (possibly mutated by listeners).
     */
    public function dispatch(object $event): object;
}
