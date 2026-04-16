# پایگان (Paygan)

> تحویل درون‌شهری / لحظه‌ای. REST API. پشتیبانی از پس‌کرایه.

## پیکربندی

```php
use Eram\Ersal\Provider\Paygan\PayganConfig;

$config = new PayganConfig(
    apiKey: 'your-api-key',
    sandbox: false,
    baseUrl: null,
);
```

## قابلیت‌ها

| رابط | پشتیبانی |
|------|---------|
| `ShippingInterface` | ✅ |
| `SupportsCOD` | ✅ |
| `SupportsLabel` | ❌ |
| `SupportsPickup` | ❌ (ضمنی — لحظه‌ای) |
| `SupportsBranches` | ❌ |

## راه‌اندازی

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Paygan\PayganConfig;

$paygan = (new Ersal())->create('paygan', new PayganConfig(apiKey: 'your-key'));
```

پایگان از هدر `X-Api-Key` استفاده می‌کند.

## quote()

قیمت‌گذاری وابسته به فاصله است — مختصات جغرافیایی روی آدرس‌ها دقت را افزایش می‌دهد.

```php
use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Request\QuoteRequest;

$origin = new Address(
    firstName: 'Merchant', lastName: 'X',
    phone: '09123456789',
    province: 'تهران', city: 'تهران',
    addressLine: 'خیابان ستارخان',
    lat: 35.7123, lng: 51.3456,
);

$destination = new Address(
    firstName: 'Customer', lastName: 'Y',
    phone: '09121111111',
    province: 'تهران', city: 'تهران',
    addressLine: 'خیابان شریعتی',
    lat: 35.7321, lng: 51.4567,
);

$quotes = $paygan->quote(new QuoteRequest(
    origin: $origin, destination: $destination,
    parcel: new Parcel(weightGrams: 800),
));

foreach ($quotes as $quote) {
    echo "{$quote->cost->inToman()} تومان ({$quote->etaDays} روز تحویل)\n";
}
```

## createShipment()

```php
use Eram\Ersal\Request\BookingRequest;

$shipment = $paygan->createShipment(new BookingRequest(
    origin: $origin, destination: $destination,
    parcel: new Parcel(weightGrams: 800),
    orderId: 'ORDER-PG-001',
));
```

## پس‌کرایه

```php
use Eram\Ersal\Contracts\SupportsCOD;
use Eram\Ersal\Money\Amount;

if ($paygan instanceof SupportsCOD) {
    $booking = (new BookingRequest(
        origin: $origin, destination: $destination,
        parcel: new Parcel(weightGrams: 800),
        orderId: 'ORDER-PG-COD-001',
    ))->withCashOnDelivery(Amount::fromToman(350_000));

    $shipment = $paygan->createShipment($booking);
}
```

## track()

```php
$tracked = $paygan->track($shipment->getId());

foreach ($tracked->getHistory() as $event) {
    printf("[%s] %s\n", $event->at->format('H:i'), $event->description);
}
```

## cancel()

```php
$cancelled = $paygan->cancel($shipment->getId());
```

## مدیریت خطا

```php
use Eram\Ersal\Exception\ProviderException;
use Eram\Ersal\Provider\Paygan\PayganErrorCode;

try {
    $paygan->createShipment($booking);
} catch (ProviderException $e) {
    $code = PayganErrorCode::tryFrom((int) $e->getErrorCode());
    echo $code?->message('fa') ?? $e->getMessage();
}
```

## نکات

- بهینه‌شده برای ارسال درون‌شهری یا مسافت کوتاه
- Pickup ضمنی است (لحظه‌ای) — گام جمع‌آوری مجزا ندارد
- آدرس endpointها را با مستندات توسعه‌دهنده پایگان تایید کنید
