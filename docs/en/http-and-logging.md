# HTTP & Logging

## Default transport

Ersal ships with `CurlHttpClient`, a persistent-handle implementation built on PHP's native `ext-curl`. Zero Composer dependencies, zero Guzzle, zero framework coupling.

Default settings:

- 10-second connect timeout
- 30-second request timeout
- SSL verification on
- User-Agent: `Ersal/1.0`

Override via constructor:

```php
use Eram\Ersal\Http\CurlHttpClient;

$http = new CurlHttpClient(
    connectTimeout: 5,
    timeout: 60,
    verifySsl: true,
    caBundle: '/path/to/cacert.pem',
    userAgent: 'MyApp/1.0',
);

$ersal = new Ersal(httpClient: $http);
```

## Custom HTTP client

Implement `HttpClient` to plug in Symfony HttpClient, Guzzle, or a test fake:

```php
use Eram\Ersal\Http\HttpClient;
use Eram\Ersal\Http\HttpResponse;

final class GuzzleAdapter implements HttpClient
{
    public function __construct(private \GuzzleHttp\Client $client) {}

    public function request(string $method, string $url, ?string $body = null, array $headers = []): HttpResponse
    {
        $resp = $this->client->request($method, $url, [
            'body' => $body,
            'headers' => $headers,
        ]);

        return new HttpResponse(
            $resp->getStatusCode(),
            (string) $resp->getBody(),
            array_map(fn($v) => $v[0] ?? '', $resp->getHeaders()),
        );
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
}
```

## Logging

`Logger` is a minimal single-method interface:

```php
interface Logger
{
    public function debug(string $message, array $context = []): void;
}
```

Only the `debug` level is used — providers log the URL, method, and provider name per request. For production, inject any adapter that implements this interface (Monolog, PSR-3 adapter, stderr writer, etc.).

```php
use Eram\Ersal\Http\Logger;

final class MonologAdapter implements Logger
{
    public function __construct(private \Monolog\Logger $inner) {}

    public function debug(string $message, array $context = []): void
    {
        $this->inner->debug($message, $context);
    }
}

$ersal = new Ersal(logger: new MonologAdapter($monolog));
```

Ersal's default is `NullLogger` — no output.

## SOAP

Iran Post's domestic API is SOAP-based. `SoapClientFactory` produces correctly-configured `\SoapClient` instances:

```php
use Eram\Ersal\Http\SoapClientFactory;

$soap = new SoapClientFactory(
    connectionTimeout: 10,
    responseTimeout: 30,
    cacheWsdl: true,
);

$ersal = new Ersal(soapFactory: $soap);
```

Default configuration enables UTF-8 encoding, WSDL caching, and strict SSL verification. Override `create()` options via the `$options` array if a particular WSDL needs custom settings.
