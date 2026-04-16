<?php

declare(strict_types=1);

namespace Eram\Ersal\Contracts;

use Eram\Ersal\Catalog\Branch;

/**
 * Implemented by providers that expose a directory of physical drop-off branches.
 */
interface SupportsBranches
{
    /**
     * @return list<Branch>
     */
    public function listBranches(?string $city = null): array;
}
