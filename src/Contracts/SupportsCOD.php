<?php

declare(strict_types=1);

namespace Eram\Ersal\Contracts;

/**
 * Marker interface: implemented by providers that accept a cash-on-delivery
 * amount on BookingRequest (BookingRequest::$codAmount).
 *
 * There is no extra method — COD is set as an attribute of a booking, not
 * a separate lifecycle verb. Use `instanceof SupportsCOD` to check at runtime
 * before populating `codAmount`.
 */
interface SupportsCOD {}
