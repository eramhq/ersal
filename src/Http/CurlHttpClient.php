<?php

declare(strict_types=1);

namespace Eram\Ersal\Http;

use Eram\Ersal\Exception\ConnectionException;

/**
 * Default HTTP client using PHP's native ext-curl.
 *
 * Reuses a persistent curl handle for TCP/TLS connection reuse across
 * sequential requests to the same carrier host (quote → book → track).
 */
final class CurlHttpClient implements HttpClient
{
    private ?\CurlHandle $handle = null;

    /** @var array<string, string> */
    private array $responseHeaders = [];

    public function __construct(
        private readonly int $connectTimeout = 10,
        private readonly int $timeout = 30,
        private readonly bool $verifySsl = true,
        private readonly ?string $caBundle = null,
        private readonly string $userAgent = 'Ersal/1.0',
    ) {}

    public function __destruct()
    {
        if ($this->handle !== null) {
            curl_close($this->handle);
        }
    }

    public function request(string $method, string $url, ?string $body = null, array $headers = []): HttpResponse
    {
        $method = strtoupper($method);

        $mergedHeaders = $headers + [
            'Accept' => 'application/json',
        ];

        if ($body !== null && !isset($mergedHeaders['Content-Type'])) {
            $mergedHeaders['Content-Type'] = 'application/json';
        }

        $curlHeaders = [];
        foreach ($mergedHeaders as $name => $value) {
            $curlHeaders[] = "{$name}: {$value}";
        }

        $this->responseHeaders = [];
        $ch = $this->getHandle();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HEADERFUNCTION => [$this, 'headerCallback'],
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $options);

        if ($this->caBundle !== null) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->caBundle);
        }

        $responseBody = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($responseBody === false || $errno !== 0) {
            throw new ConnectionException(
                \sprintf('HTTP %s request to %s failed: %s (curl error %d)', $method, $url, $error, $errno),
            );
        }

        return new HttpResponse($statusCode, (string) $responseBody, $this->responseHeaders);
    }

    public function postJson(string $url, string $body, array $headers = []): HttpResponse
    {
        return $this->request('POST', $url, $body, $headers + ['Content-Type' => 'application/json']);
    }

    public function getJson(string $url, array $headers = []): HttpResponse
    {
        return $this->request('GET', $url, null, $headers);
    }

    public function deleteJson(string $url, array $headers = []): HttpResponse
    {
        return $this->request('DELETE', $url, null, $headers);
    }

    private function getHandle(): \CurlHandle
    {
        if ($this->handle === null) {
            $handle = curl_init();

            if ($handle === false) {
                throw new ConnectionException('Failed to initialize curl handle.');
            }

            $this->handle = $handle;
        } else {
            curl_reset($this->handle);
        }

        return $this->handle;
    }

    private function headerCallback(\CurlHandle $ch, string $header): int
    {
        $len = \strlen($header);
        $colonPos = strpos($header, ':');

        if ($colonPos !== false) {
            $name = strtolower(trim(substr($header, 0, $colonPos)));
            $value = trim(substr($header, $colonPos + 1));
            $this->responseHeaders[$name] = $value;
        }

        return $len;
    }
}
