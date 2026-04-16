<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider;

use Eram\Ersal\Contracts\ShippingInterface;
use Eram\Ersal\Exception\ConnectionException;
use Eram\Ersal\Http\EventDispatcher;
use Eram\Ersal\Http\HttpClient;
use Eram\Ersal\Http\HttpResponse;
use Eram\Ersal\Http\Logger;
use Eram\Ersal\Http\NullLogger;

/**
 * Base class for REST-based shipping providers.
 */
abstract class AbstractProvider implements ShippingInterface
{
    use ProviderHelperTrait;

    protected HttpClient $httpClient;
    protected Logger $logger;

    public function __construct(
        HttpClient $httpClient,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger ?? new NullLogger();
        $this->eventDispatcher = $eventDispatcher;
    }

    abstract public function getName(): string;

    /**
     * POST JSON to the carrier API and return the decoded response body.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @return array<string, mixed>
     * @throws ConnectionException On transport, encode, or decode failure.
     */
    protected function postJson(string $url, array $data, array $headers = []): array
    {
        try {
            $jsonBody = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw new ConnectionException("Failed to encode request body: {$e->getMessage()}", 0, $e);
        }

        $this->logger->debug('Ersal: POST', [
            'provider' => $this->getName(),
            'url' => $url,
        ]);

        return $this->decode($this->httpClient->postJson($url, $jsonBody, $headers), $url);
    }

    /**
     * GET JSON from the carrier API and return the decoded response body.
     *
     * @param array<string, string> $headers
     * @return array<string, mixed>
     * @throws ConnectionException
     */
    protected function getJson(string $url, array $headers = []): array
    {
        $this->logger->debug('Ersal: GET', [
            'provider' => $this->getName(),
            'url' => $url,
        ]);

        return $this->decode($this->httpClient->getJson($url, $headers), $url);
    }

    /**
     * DELETE on the carrier API and return the decoded response body.
     *
     * @param array<string, string> $headers
     * @return array<string, mixed>
     * @throws ConnectionException
     */
    protected function deleteJson(string $url, array $headers = []): array
    {
        $this->logger->debug('Ersal: DELETE', [
            'provider' => $this->getName(),
            'url' => $url,
        ]);

        return $this->decode($this->httpClient->deleteJson($url, $headers), $url);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(HttpResponse $response, string $url): array
    {
        if ($response->body === '') {
            return [];
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ConnectionException(
                \sprintf('Failed to decode response from %s: %s', $url, $e->getMessage()),
                0,
                $e,
            );
        }

        return $decoded;
    }
}
