<?php

declare(strict_types=1);

namespace Eram\Ersal\Http;

use Eram\Ersal\Exception\ConnectionException;

/**
 * Minimal HTTP client contract for shipping providers.
 *
 * Implement this interface to plug in a custom HTTP client (e.g., adapters
 * wrapping Symfony HttpClient, Guzzle, or a mock for testing).
 *
 * Shipping APIs commonly use all four verbs: POST to book, GET to track,
 * DELETE to cancel, PUT to update. All methods converge on `request()`.
 */
interface HttpClient
{
    /**
     * Send an arbitrary HTTP request.
     *
     * @param array<string, string> $headers
     * @throws ConnectionException On transport-level failures (DNS, timeout, TLS, etc.).
     */
    public function request(string $method, string $url, ?string $body = null, array $headers = []): HttpResponse;

    /**
     * Send a JSON POST request to the given URL.
     *
     * @param array<string, string> $headers
     * @throws ConnectionException On transport-level failures (DNS, timeout, TLS, etc.).
     */
    public function postJson(string $url, string $body, array $headers = []): HttpResponse;

    /**
     * Send a GET request to the given URL.
     *
     * @param array<string, string> $headers
     * @throws ConnectionException On transport-level failures.
     */
    public function getJson(string $url, array $headers = []): HttpResponse;

    /**
     * Send a DELETE request to the given URL.
     *
     * @param array<string, string> $headers
     * @throws ConnectionException On transport-level failures.
     */
    public function deleteJson(string $url, array $headers = []): HttpResponse;
}
