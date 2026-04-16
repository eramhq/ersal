<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider;

use Eram\Ersal\Contracts\ShippingInterface;
use Eram\Ersal\Exception\ConnectionException;
use Eram\Ersal\Http\EventDispatcher;
use Eram\Ersal\Http\Logger;
use Eram\Ersal\Http\NullLogger;
use Eram\Ersal\Http\SoapClientFactory;

/**
 * Base class for SOAP-based shipping providers (Iran Post domestic services).
 */
abstract class AbstractSoapProvider implements ShippingInterface
{
    use ProviderHelperTrait;

    protected SoapClientFactory $soapFactory;
    protected Logger $logger;
    private ?\SoapClient $client = null;

    public function __construct(
        ?SoapClientFactory $soapFactory = null,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        $this->soapFactory = $soapFactory ?? new SoapClientFactory();
        $this->logger = $logger ?? new NullLogger();
        $this->eventDispatcher = $eventDispatcher;
    }

    abstract public function getName(): string;

    /**
     * WSDL URL for this provider's SOAP service.
     */
    abstract protected function getWsdlUrl(): string;

    /**
     * Get or lazily create the SoapClient.
     */
    protected function getSoapClient(): \SoapClient
    {
        if ($this->client === null) {
            $this->client = $this->soapFactory->create($this->getWsdlUrl());
        }

        return $this->client;
    }

    /**
     * Call a SOAP method with error handling.
     *
     * @param array<int|string, mixed> $params
     */
    protected function callSoap(string $method, array $params): mixed
    {
        $this->logger->debug('Ersal: SOAP call', [
            'provider' => $this->getName(),
            'method' => $method,
        ]);

        try {
            $client = $this->getSoapClient();

            return $client->__soapCall($method, [$params]);
        } catch (\SoapFault $e) {
            $this->client = null;

            throw new ConnectionException(
                \sprintf(
                    'SOAP call %s::%s failed: %s',
                    $this->getName(),
                    $method,
                    $e->getMessage(),
                ),
                0,
                $e,
            );
        }
    }
}
