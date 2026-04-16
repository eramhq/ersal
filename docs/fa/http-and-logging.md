# HTTP و لاگ

## Transport پیش‌فرض

ارسال با `CurlHttpClient` می‌آید — پیاده‌سازی با persistent handle روی `ext-curl` native. بدون وابستگی Composer، بدون Guzzle، بدون وابستگی به فریم‌ورک.

تنظیمات پیش‌فرض:

- timeout اتصال: ۱۰ ثانیه
- timeout درخواست: ۳۰ ثانیه
- تایید SSL فعال
- User-Agent: `Ersal/1.0`

از طریق سازنده override کنید:

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

## HTTP Client سفارشی

`HttpClient` را پیاده‌سازی کنید تا Symfony HttpClient، Guzzle یا یک fake تست وصل کنید:

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

## لاگ

`Logger` یک interface حداقلی با یک متد است:

```php
interface Logger
{
    public function debug(string $message, array $context = []): void;
}
```

فقط سطح `debug` استفاده می‌شود — providerها آدرس، متد و نام provider را برای هر درخواست لاگ می‌کنند. برای production، هر آداپتری که این interface را پیاده‌سازی کند تزریق کنید (Monolog، آداپتر PSR-3، نویسنده stderr و غیره).

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

پیش‌فرض ارسال `NullLogger` است — خروجی ندارد.

## SOAP

API داخلی پست ایران مبتنی بر SOAP است. `SoapClientFactory` نمونه‌های `\SoapClient` با پیکربندی درست تولید می‌کند:

```php
use Eram\Ersal\Http\SoapClientFactory;

$soap = new SoapClientFactory(
    connectionTimeout: 10,
    responseTimeout: 30,
    cacheWsdl: true,
);

$ersal = new Ersal(soapFactory: $soap);
```

پیکربندی پیش‌فرض encoding UTF-8، WSDL caching و تایید strict SSL را فعال می‌کند. گزینه‌های `create()` را از طریق آرایه `$options` override کنید اگر WSDL خاص تنظیمات متفاوت می‌خواهد.
