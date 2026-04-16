# تیپاکس (Tipax)

> شرکت حمل خصوصی سراسری. REST API. پشتیبانی از label، pickup، شعب و پس‌کرایه.

## پیکربندی

```php
use Eram\Ersal\Provider\Tipax\TipaxConfig;

$config = new TipaxConfig(
    token: 'your-bearer-token',
    sandbox: false,
    baseUrl: null,
);
```

| پارامتر | نوع | اجباری | پیش‌فرض | توضیح |
|---------|-----|--------|---------|-------|
| `token` | `string` | بله | — | توکن Bearer از پرتال توسعه‌دهنده تیپاکس |
| `sandbox` | `bool` | خیر | `false` | محیط تست |
| `baseUrl` | `?string` | خیر | `null` | بازنویسی آدرس API |

## قابلیت‌ها

| رابط | پشتیبانی |
|------|---------|
| `ShippingInterface` (quote, book, track, cancel) | ✅ |
| `SupportsLabel` | ✅ |
| `SupportsPickup` | ✅ |
| `SupportsBranches` | ✅ |
| `SupportsCOD` | ✅ |

## راه‌اندازی

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Tipax\TipaxConfig;

$ersal = new Ersal();
$tipax = $ersal->create('tipax', new TipaxConfig(token: 'your-token'));
```

## quote() — استعلام قیمت

`list<Quote>` برمی‌گرداند. تیپاکس می‌تواند چند پیشنهاد (یکی به ازای هر سطح سرویس) بدهد.

```php
use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Money\Amount;
use Eram\Ersal\Request\QuoteRequest;

$origin = new Address(
    firstName: 'Shop', lastName: 'Owner',
    phone: '09123456789',
    province: 'تهران', city: 'تهران',
    addressLine: 'خیابان ولیعصر، پلاک ۱۰۰',
    postalCode: '1234567890',
);

$destination = new Address(
    firstName: 'Customer', lastName: 'Name',
    phone: '09121111111',
    province: 'اصفهان', city: 'اصفهان',
    addressLine: 'خیابان چهارباغ، پلاک ۲۰',
    postalCode: '8159876543',
);

$parcel = new Parcel(
    weightGrams: 1500,
    lengthMm: 300, widthMm: 200, heightMm: 100,
    declaredValue: Amount::fromToman(500_000),
    contentsDescription: 'کتاب',
);

$quotes = $tipax->quote(new QuoteRequest(
    origin: $origin, destination: $destination, parcel: $parcel,
));

foreach ($quotes as $quote) {
    printf(
        "%s: %d تومان (تحویل %d روز) — شناسه: %s\n",
        $quote->serviceLevel,
        $quote->cost->inToman(),
        $quote->etaDays,
        $quote->quoteId,
    );
}
```

## createShipment() — ثبت مرسوله

```php
use Eram\Ersal\Request\BookingRequest;

$shipment = $tipax->createShipment(new BookingRequest(
    origin: $origin, destination: $destination, parcel: $parcel,
    orderId: 'ORDER-2026-0001',
    serviceLevel: 'express',
    quoteId: $quotes[0]->quoteId,
    description: 'کتاب',
));

$shipment->getId();              // ShipmentId — برای فراخوانی‌های بعدی
$shipment->getTrackingCode();    // کد رهگیری قابل نمایش به مشتری
$shipment->getStatus();          // ShipmentStatus::Booked
$shipment->getCost()->inToman(); // هزینه نهایی
```

## پس‌کرایه (Cash-on-delivery)

```php
use Eram\Ersal\Contracts\SupportsCOD;

if ($tipax instanceof SupportsCOD) {
    $booking = (new BookingRequest(
        origin: $origin, destination: $destination, parcel: $parcel,
        orderId: 'ORDER-2026-0002',
    ))->withCashOnDelivery(Amount::fromToman(2_500_000));

    $shipment = $tipax->createShipment($booking);
}
```

برای COD، تیپاکس معمولاً `Address::$nationalId` گیرنده را می‌خواهد.

## track() — وضعیت و تاریخچه

```php
$tracked = $tipax->track($shipment->getId());

$tracked->getStatus();                // ShipmentStatus
$tracked->getStatus()->label('fa');   // 'در حال ارسال'
$tracked->getStatus()->isTerminal();  // تا نرسیدن false

foreach ($tracked->getHistory() as $event) {
    printf(
        "[%s] %s — %s در %s\n",
        $event->at->format('Y-m-d H:i'),
        $event->status->label('fa'),
        $event->description,
        $event->location ?? 'نامشخص',
    );
}
```

## cancel() — لغو

```php
use Eram\Ersal\Exception\CancellationException;

try {
    $cancelled = $tipax->cancel($shipment->getId());
    $cancelled->getStatus(); // ShipmentStatus::Cancelled
} catch (CancellationException $e) {
    echo $e->getMessage();
}
```

## getLabel() — دریافت برچسب

```php
use Eram\Ersal\Contracts\SupportsLabel;

if ($tipax instanceof SupportsLabel) {
    $label = $tipax->getLabel($shipment->getId());
    file_put_contents("labels/{$shipment->getId()}.{$label->format}", $label->bytes);

    if ($label->hasUrl()) {
        echo "لینک برچسب: {$label->url}\n";
    }
}
```

## schedulePickup() — درخواست جمع‌آوری پیک

```php
use Eram\Ersal\Contracts\SupportsPickup;
use Eram\Ersal\Request\PickupRequest;

if ($tipax instanceof SupportsPickup) {
    $scheduled = $tipax->schedulePickup(
        $shipment->getId(),
        new PickupRequest(
            windowStart: new DateTimeImmutable('tomorrow 10:00'),
            windowEnd: new DateTimeImmutable('tomorrow 12:00'),
            instructions: 'در ورودی اصلی ساختمان، پلاک ۱۰۰ واحد ۳',
        ),
    );
}
```

## listBranches() — فهرست شعب

```php
use Eram\Ersal\Contracts\SupportsBranches;

if ($tipax instanceof SupportsBranches) {
    $branches = $tipax->listBranches('تهران'); // null = همه شهرها

    foreach ($branches as $branch) {
        printf("%s — %s (تلفن: %s)\n",
            $branch->name, $branch->address, $branch->phone ?? 'ندارد');
    }
}
```

## مدیریت خطا

```php
use Eram\Ersal\Exception\BookingException;
use Eram\Ersal\Exception\ConnectionException;
use Eram\Ersal\Provider\Tipax\TipaxErrorCode;

try {
    $shipment = $tipax->createShipment($booking);
} catch (BookingException $e) {
    $code = TipaxErrorCode::tryFrom((int) $e->getErrorCode());
    echo $code?->message('fa') ?? $e->getMessage();
} catch (ConnectionException $e) {
    // خطای شبکه — می‌توان دوباره تلاش کرد
}
```

## نکات

- هزینه به ریال ارسال و دریافت می‌شود
- وزن به گرم، ابعاد به میلی‌متر
- آدرس endpointها را با مستندات توسعه‌دهنده تیپاکس تایید کنید
