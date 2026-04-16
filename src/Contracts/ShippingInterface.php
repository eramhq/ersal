<?php

declare(strict_types=1);

namespace Eram\Ersal\Contracts;

use Eram\Ersal\Request\BookingRequest;
use Eram\Ersal\Request\Quote;
use Eram\Ersal\Request\QuoteRequest;
use Eram\Ersal\Shipment\ShipmentId;

interface ShippingInterface
{
    /**
     * The shipping provider's alias (e.g. 'tipax', 'post').
     */
    public function getName(): string;

    /**
     * Price a shipment without creating it.
     *
     * Carriers may return multiple quotes (one per service level — e.g.
     * express vs standard). Carriers that only expose a single price
     * return a single-element list.
     *
     * @return list<Quote>
     */
    public function quote(QuoteRequest $request): array;

    /**
     * Create (book) a shipment and receive its assigned tracking code.
     */
    public function createShipment(BookingRequest $request): ShipmentInterface;

    /**
     * Pull the current status + tracking history for an existing shipment.
     */
    public function track(ShipmentId $id): ShipmentInterface;

    /**
     * Cancel a shipment if the carrier and its current state allow it.
     */
    public function cancel(ShipmentId $id): ShipmentInterface;
}
