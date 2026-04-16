# شروع سریع

## پیش‌نیازها

- PHP 8.1 به بالا
- اکستنشن‌ها: `ext-curl`، `ext-json`، `ext-openssl`، `ext-soap`

## نصب

```bash
composer require eram/ersal
```

ارسال **بدون** هیچ وابستگی Composer کار می‌کند. فقط به اکستنشن‌های استاندارد PHP تکیه دارد.

## مثال سریع

یک جریان کامل با تیپاکس:

### ۱. ساخت Provider

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Tipax\TipaxConfig;

$ersal = new Ersal();
$provider = $ersal->create('tipax', new TipaxConfig(
    token: 'your-api-token',
));
```

### ۲. دریافت استعلام قیمت

```php
use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Request\QuoteRequest;

$origin = new Address(
    firstName: 'فروشگاه', lastName: 'من',
    phone: '09123456789',
    province: 'تهران', city: 'تهران',
    addressLine: 'خیابان ولیعصر، پلاک ۱۰۰',
    postalCode: '1234567890',
);

$destination = new Address(
    firstName: 'مشتری', lastName: 'عزیز',
    phone: '09121111111',
    province: 'اصفهان', city: 'اصفهان',
    addressLine: 'خیابان چهارباغ، پلاک ۲۰',
    postalCode: '8159876543',
);

$parcel = new Parcel(
    weightGrams: 1500,                         // ۱٫۵ کیلوگرم
    lengthMm: 300, widthMm: 200, heightMm: 100,
);

$quotes = $provider->quote(new QuoteRequest(
    origin: $origin, destination: $destination, parcel: $parcel,
));

foreach ($quotes as $quote) {
    echo "{$quote->serviceLevel}: {$quote->cost->inToman()} تومان (تحویل {$quote->etaDays} روز)\n";
}
```

### ۳. ثبت مرسوله

```php
use Eram\Ersal\Request\BookingRequest;

$shipment = $provider->createShipment(new BookingRequest(
    origin: $origin, destination: $destination, parcel: $parcel,
    orderId: 'ORDER-2026-0001',
    serviceLevel: 'standard',
    quoteId: $quotes[0]->quoteId,
));

echo $shipment->getTrackingCode();
echo $shipment->getStatus()->label('fa');
```

### ۴. ردیابی مرسوله

```php
$tracked = $provider->track($shipment->getId());

foreach ($tracked->getHistory() as $event) {
    echo $event->at->format('Y-m-d H:i');
    echo ' — ' . $event->status->label('fa');
    echo ' — ' . $event->description . "\n";
}
```

### ۵. لغو (در صورت نیاز)

```php
$cancelled = $provider->cancel($shipment->getId());
```

## Providerهای پشتیبانی‌شده

| نام | شرکت | پروتکل | قابلیت‌ها |
|-----|-------|--------|---------|
| `post` | پست ایران | SOAP | Label، Branches |
| `tipax` | تیپاکس | REST | Label، Pickup، Branches، COD |
| `chapar` | چاپار | REST | Label، Pickup، COD |
| `mahex` | ماهکس | REST | Pickup، COD |
| `amadast` | آمادست | REST | — |
| `paygan` | پایگان | REST | COD |
| `alopeyk` | الوپیک | REST | Pickup |

## گام‌های بعدی

- [مفاهیم اصلی](concepts.md)
- [دستورپخت](cookbook.md)
- [کاتالوگ Providerها](README.md#کاتالوگ-providerها)
