<?php

declare(strict_types=1);

namespace Eram\Ersal\Http;

use Eram\Ersal\Exception\ConnectionException;

/**
 * Factory for creating configured SoapClient instances for Iranian shipping carriers.
 *
 * Handles common issues: WSDL caching, connection timeouts, encoding fixes.
 */
class SoapClientFactory
{
    private int $connectionTimeout;
    private int $responseTimeout;
    private bool $cacheWsdl;

    public function __construct(
        int $connectionTimeout = 10,
        int $responseTimeout = 30,
        bool $cacheWsdl = true,
    ) {
        $this->connectionTimeout = $connectionTimeout;
        $this->responseTimeout = $responseTimeout;
        $this->cacheWsdl = $cacheWsdl;
    }

    /**
     * Create a SoapClient for the given WSDL URL.
     *
     * @param array<string, mixed> $options Additional SoapClient options.
     */
    public function create(string $wsdlUrl, array $options = []): \SoapClient
    {
        $defaults = [
            'encoding' => 'UTF-8',
            'trace' => false,
            'exceptions' => true,
            'connection_timeout' => $this->connectionTimeout,
            'default_socket_timeout' => $this->responseTimeout,
            'cache_wsdl' => $this->cacheWsdl ? WSDL_CACHE_BOTH : WSDL_CACHE_NONE,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]),
        ];

        try {
            return new \SoapClient($wsdlUrl, array_merge($defaults, $options));
        } catch (\SoapFault $e) {
            throw new ConnectionException(
                \sprintf('Failed to connect to SOAP service at %s: %s', $wsdlUrl, $e->getMessage()),
                0,
                $e,
            );
        }
    }
}
