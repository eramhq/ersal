<?php

declare(strict_types=1);

namespace Eram\Ersal\Http;

/**
 * Minimal logger contract used internally by Ersal.
 *
 * Only the `debug` level is used — providers log the URL and provider name
 * when sending requests so you can trace HTTP and SOAP calls in development.
 */
interface Logger
{
    /**
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void;
}
