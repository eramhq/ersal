<?php

declare(strict_types=1);

namespace Eram\Ersal\Http;

final class NullLogger implements Logger
{
    public function debug(string $message, array $context = []): void {}
}
