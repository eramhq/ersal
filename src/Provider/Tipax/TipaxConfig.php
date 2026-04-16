<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Tipax;

final class TipaxConfig
{
    public function __construct(
        /**
         * Bearer API token issued by Tipax's developer portal.
         */
        public readonly string $token,
        /**
         * Use the sandbox environment for integration testing.
         */
        public readonly bool $sandbox = false,
        /**
         * Optional override for the API base URL — useful if Tipax provides
         * a customer-specific endpoint or for self-hosted mocking.
         */
        public readonly ?string $baseUrl = null,
    ) {}
}
