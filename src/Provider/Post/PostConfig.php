<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Post;

final class PostConfig
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
        /**
         * Contract / merchant code issued by Iran Post for API access.
         */
        public readonly string $contractCode,
        public readonly bool $sandbox = false,
        public readonly ?string $wsdlUrl = null,
    ) {}
}
