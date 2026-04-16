<?php

declare(strict_types=1);

namespace Eram\Ersal\Contracts;

use Eram\Ersal\Request\LabelResponse;
use Eram\Ersal\Shipment\ShipmentId;

/**
 * Implemented by providers that can issue printable shipping labels.
 */
interface SupportsLabel
{
    public function getLabel(ShipmentId $id): LabelResponse;
}
