<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Chapar;

final class ChaparConfig
{
    public function __construct(
        public readonly string $apiKey,
        public readonly bool $sandbox = false,
        public readonly ?string $baseUrl = null,
    ) {}
}
