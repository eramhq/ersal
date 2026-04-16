# چاپار (Chapar Express)

> شرکت حمل خصوصی. REST API. پشتیبانی از برچسب، pickup و پس‌کرایه.

## پیکربندی

```php
use Eram\Ersal\Provider\Chapar\ChaparConfig;

$config = new ChaparConfig(
    apiKey: 'your-api-key',
    sandbox: false,
    baseUrl: null,
);
```

| پارامتر | نوع | اجباری | پیش‌فرض | توضیح |
|---------|-----|--------|---------|-------|
| `apiKey` | `string` | بله | — | کلید API از پرتال چاپار |
| `sandbox` | `bool` | خیر | `false` | محیط تست |
| `baseUrl` | `?string` | خیر | `null` | بازنویسی آدرس API |

## قابلیت‌ها

| رابط | پشتیبانی |
|------|---------|
| `ShippingInterface` | ✅ |
| `SupportsLabel` | ✅ |
| `SupportsPickup` | ✅ |
| `SupportsCOD` | ✅ |
| `SupportsBranches` | ❌ |

## راه‌اندازی

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Chapar\ChaparConfig;

$chapar = (new Ersal())->create('chapar', new ChaparConfig(apiKey: 'your-key'));
```

چاپار از هدر `X-Api-Key` استفاده می‌کند.

## quote()

```php
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Request\QuoteRequest;

$quotes = $chapar->quote(new QuoteRequest(
    origin: $origin, destination: $destination,
    parcel: new Parcel(weightGrams: 1000, lengthMm: 200, widthMm: 150, heightMm: 80),
));

foreach ($quotes as $quote) {
    printf("%s — %s تومان، تحویل %s روز\n",
        $quote->serviceLevel, $quote->cost->inToman(), $quote->etaDays);
}
```

## createShipment()

```php
use Eram\Ersal\Request\BookingRequest;

$shipment = $chapar->createShipment(new BookingRequest(
    origin: $origin, destination: $destination, parcel: $parcel,
    orderId: 'ORDER-CHP-001',
    serviceLevel: 'standard',
    quoteId: $quotes[0]->quoteId,
    description: 'وسایل الکترونیکی',
));
```

## پس‌کرایه

```php
use Eram\Ersal\Contracts\SupportsCOD;
use Eram\Ersal\Money\Amount;

if ($chapar instanceof SupportsCOD) {
    $booking = (new BookingRequest(
        origin: $origin, destination: $destination, parcel: $parcel,
        orderId: 'ORDER-CHP-COD-001',
    ))->withCashOnDelivery(Amount::fromToman(1_200_000));

    $shipment = $chapar->createShipment($booking);
}
```

## track()

```php
$tracked = $chapar->track($shipment->getId());

foreach ($tracked->getHistory() as $event) {
    printf("[%s] %s\n", $event->at->format('Y-m-d H:i'), $event->description);
}
```

## cancel()

```php
$cancelled = $chapar->cancel($shipment->getId());
```

## getLabel()

```php
use Eram\Ersal\Contracts\SupportsLabel;

if ($chapar instanceof SupportsLabel) {
    $label = $chapar->getLabel($shipment->getId());
    file_put_contents("chapar-{$shipment->getId()}.pdf", $label->bytes);
}
```

## schedulePickup()

```php
use Eram\Ersal\Contracts\SupportsPickup;
use Eram\Ersal\Request\PickupRequest;

if ($chapar instanceof SupportsPickup) {
    $chapar->schedulePickup($shipment->getId(), new PickupRequest(
        windowStart: new DateTimeImmutable('tomorrow 9:00'),
        windowEnd: new DateTimeImmutable('tomorrow 11:00'),
        instructions: 'پشت در تحویل دهید',
    ));
}
```

## مدیریت خطا

```php
use Eram\Ersal\Exception\ProviderException;
use Eram\Ersal\Provider\Chapar\ChaparErrorCode;

try {
    $chapar->createShipment($booking);
} catch (ProviderException $e) {
    $code = ChaparErrorCode::tryFrom((int) $e->getErrorCode());
    echo $code?->message('fa') ?? $e->getMessage();
}
```

## نکات

- هزینه به ریال، وزن به گرم، ابعاد به میلی‌متر
- آدرس endpointها را با مستندات توسعه‌دهنده چاپار تایید کنید
