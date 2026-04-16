<?php

declare(strict_types=1);

namespace Eram\Ersal\Http;

/**
 * Immutable value object representing an HTTP response.
 */
final class HttpResponse
{
    /**
     * @param array<string, string> $headers Lower-cased header names for case-insensitive access.
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
        public readonly array $headers = [],
    ) {}

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
