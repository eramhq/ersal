# آمادست (Amadast)

> تجمیع‌کننده چند شرکتی / کارگزار last-mile. REST API.

## پیکربندی

```php
use Eram\Ersal\Provider\Amadast\AmadastConfig;

$config = new AmadastConfig(
    apiKey: 'your-api-key',
    sandbox: false,
    baseUrl: null,
);
```

## قابلیت‌ها

| رابط | پشتیبانی |
|------|---------|
| `ShippingInterface` | ✅ |
| `SupportsLabel` | ❌ |
| `SupportsPickup` | ❌ |
| `SupportsBranches` | ❌ |
| `SupportsCOD` | ❌ |

آمادست پیشنهادات چند شرکت را تجمیع می‌کند — label/pickup/COD را شرکتی که آمادست به آن مسیریابی می‌کند ارائه می‌دهد، نه خود آمادست.

## راه‌اندازی

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Amadast\AmadastConfig;

$amadast = (new Ersal())->create('amadast', new AmadastConfig(apiKey: 'your-key'));
```

## quote() — مقایسه شرکت‌ها در یک فراخوان

آمادست چند پیشنهاد از شرکت‌های مختلف برمی‌گرداند. نام شرکت معمولاً در `Quote::$extra` قرار می‌گیرد.

```php
use Eram\Ersal\Request\QuoteRequest;

$offers = $amadast->quote(new QuoteRequest(
    origin: $origin, destination: $destination, parcel: $parcel,
));

foreach ($offers as $offer) {
    $carrier = $offer->extra['carrier'] ?? 'نامشخص';
    printf("%s از طریق %s — %s تومان، تحویل %s روز (پیشنهاد: %s)\n",
        $offer->serviceLevel, $carrier,
        $offer->cost->inToman(), $offer->etaDays, $offer->quoteId);
}

// انتخاب ارزان‌ترین
usort($offers, fn($a, $b) => $a->cost->inRials() <=> $b->cost->inRials());
$cheapest = $offers[0];
```

## createShipment() — ثبت روی پیشنهاد انتخابی

```php
use Eram\Ersal\Request\BookingRequest;

$shipment = $amadast->createShipment(new BookingRequest(
    origin: $origin, destination: $destination, parcel: $parcel,
    orderId: 'ORDER-AMD-001',
    quoteId: $cheapest->quoteId,
));
```

## track()

```php
$tracked = $amadast->track($shipment->getId());

// رویدادهای ردیابی شرکت زیربنا بدون تغییر پاس می‌شوند
foreach ($tracked->getHistory() as $event) {
    printf("[%s] %s — %s\n",
        $event->at->format('Y-m-d H:i'),
        $event->status->label('fa'), $event->description);
}
```

## cancel()

```php
use Eram\Ersal\Exception\CancellationException;

try {
    $cancelled = $amadast->cancel($shipment->getId());
} catch (CancellationException $e) {
    // شرکت زیربنا لغو را قبول نکرد (مثلاً جمع‌آوری انجام شده)
}
```

## مدیریت خطا

```php
use Eram\Ersal\Exception\ProviderException;
use Eram\Ersal\Provider\Amadast\AmadastErrorCode;

try {
    $amadast->createShipment($booking);
} catch (ProviderException $e) {
    $code = AmadastErrorCode::tryFrom((int) $e->getErrorCode());
    echo $code?->message('fa') ?? $e->getMessage();
}
```

## نکات

- از هدر `X-Api-Key` استفاده می‌کند
- اگر quote شامل `quoteId` نباشد، در زمان ثبت دوباره قیمت گرفته می‌شود — احتمال تغییر قیمت وجود دارد
- موفقیت لغو به شرکت زیربنای انتخاب‌شده توسط آمادست بستگی دارد
