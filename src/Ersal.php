<?php

declare(strict_types=1);

namespace Eram\Ersal;

use Eram\Ersal\Contracts\ShippingInterface;
use Eram\Ersal\Http\CurlHttpClient;
use Eram\Ersal\Http\EventDispatcher;
use Eram\Ersal\Http\HttpClient;
use Eram\Ersal\Http\Logger;
use Eram\Ersal\Http\NullLogger;
use Eram\Ersal\Http\SoapClientFactory;
use Eram\Ersal\Provider\Alopeyk\AlopeykConfig;
use Eram\Ersal\Provider\Alopeyk\AlopeykProvider;
use Eram\Ersal\Provider\Amadast\AmadastConfig;
use Eram\Ersal\Provider\Amadast\AmadastProvider;
use Eram\Ersal\Provider\Chapar\ChaparConfig;
use Eram\Ersal\Provider\Chapar\ChaparProvider;
use Eram\Ersal\Provider\Mahex\MahexConfig;
use Eram\Ersal\Provider\Mahex\MahexProvider;
use Eram\Ersal\Provider\Paygan\PayganConfig;
use Eram\Ersal\Provider\Paygan\PayganProvider;
use Eram\Ersal\Provider\Post\PostConfig;
use Eram\Ersal\Provider\Post\PostProvider;
use Eram\Ersal\Provider\Tipax\TipaxConfig;
use Eram\Ersal\Provider\Tipax\TipaxProvider;

/**
 * Main entry point for the Ersal shipping library.
 *
 * Usage (zero-config):
 *   $ersal = new Ersal();
 *   $provider = $ersal->create('tipax', new TipaxConfig('your-token'));
 *   $quotes = $provider->quote($quoteRequest);
 */
final class Ersal
{
    private HttpClient $httpClient;
    private Logger $logger;
    private ?EventDispatcher $eventDispatcher;
    private ?SoapClientFactory $soapFactory;

    /**
     * All parameters are optional — defaults use native ext-curl and ext-soap.
     */
    public function __construct(
        ?HttpClient $httpClient = null,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
        ?SoapClientFactory $soapFactory = null,
    ) {
        $this->httpClient = $httpClient ?? new CurlHttpClient();
        $this->logger = $logger ?? new NullLogger();
        $this->eventDispatcher = $eventDispatcher;
        $this->soapFactory = $soapFactory;
    }

    /**
     * Create a provider instance by alias.
     *
     * @param object $config Provider-specific config DTO (e.g., TipaxConfig, PostConfig).
     */
    public function create(string $provider, object $config): ShippingInterface
    {
        return match ($provider) {
            'tipax' => new TipaxProvider(
                self::ensure($config, TipaxConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'chapar' => new ChaparProvider(
                self::ensure($config, ChaparConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'mahex' => new MahexProvider(
                self::ensure($config, MahexConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'paygan' => new PayganProvider(
                self::ensure($config, PayganConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'alopeyk' => new AlopeykProvider(
                self::ensure($config, AlopeykConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'amadast' => new AmadastProvider(
                self::ensure($config, AmadastConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'post' => new PostProvider(
                self::ensure($config, PostConfig::class),
                $this->soapFactory(),
                $this->logger,
                $this->eventDispatcher,
            ),
            default => throw new \InvalidArgumentException(
                \sprintf('Unknown provider "%s". Available: %s', $provider, implode(', ', self::available())),
            ),
        };
    }

    /**
     * @return list<string>
     */
    public static function available(): array
    {
        return ['post', 'tipax', 'chapar', 'mahex', 'amadast', 'paygan', 'alopeyk'];
    }

    protected function soapFactory(): SoapClientFactory
    {
        return $this->soapFactory ??= new SoapClientFactory();
    }

    /**
     * @template T of object
     * @param class-string<T> $expected
     * @return T
     */
    protected static function ensure(object $config, string $expected): object
    {
        if (!$config instanceof $expected) {
            throw new \InvalidArgumentException(
                \sprintf('Expected %s, got %s', $expected, $config::class),
            );
        }

        return $config;
    }
}
