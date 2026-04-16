<?php

declare(strict_types=1);

namespace Eram\Ersal\Tests\Unit\Provider;

use Eram\Ersal\Provider\Post\PostConfig;
use Eram\Ersal\Provider\Post\PostProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Iran Post provider is SOAP-based. A full wire-level test requires stubbing
 * \SoapClient::__soapCall, which is tightly coupled to WSDL-derived types.
 * Here we verify naming + construction; integration-level SOAP tests live
 * in tests/Integration/ under a separate suite.
 */
final class PostProviderTest extends TestCase
{
    #[Test]
    public function provider_name(): void
    {
        $provider = new PostProvider(
            new PostConfig(
                username: 'u',
                password: 'p',
                contractCode: 'c',
            ),
        );

        $this->assertSame('post', $provider->getName());
    }

    #[Test]
    public function factory_yields_this_provider(): void
    {
        $ersal = new \Eram\Ersal\Ersal();
        $provider = $ersal->create('post', new PostConfig(
            username: 'u',
            password: 'p',
            contractCode: 'c',
        ));

        $this->assertInstanceOf(PostProvider::class, $provider);
    }
}
