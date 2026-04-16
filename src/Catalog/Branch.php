<?php

declare(strict_types=1);

namespace Eram\Ersal\Catalog;

/**
 * A carrier branch / drop-off point (typically used by Post and Tipax).
 */
final class Branch
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $city,
        public readonly string $address,
        public readonly ?string $phone = null,
        public readonly ?float $lat = null,
        public readonly ?float $lng = null,
        public readonly ?string $openingHours = null,
    ) {}
}
