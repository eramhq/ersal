<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Mahex;

final class MahexConfig
{
    public function __construct(
        public readonly string $token,
        public readonly bool $sandbox = false,
        public readonly ?string $baseUrl = null,
    ) {}
}
