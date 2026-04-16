# دستورپخت

## مقایسه چند شرکت حمل

```php
$ersal = new Ersal();
$request = new QuoteRequest(origin: $o, destination: $d, parcel: $p);

$cheapest = null;

foreach ([
    ['tipax', new TipaxConfig('tipax-token')],
    ['chapar', new ChaparConfig('chapar-key')],
    ['post', new PostConfig(username: 'u', password: 'p', contractCode: 'c')],
] as [$alias, $config]) {
    $provider = $ersal->create($alias, $config);

    try {
        foreach ($provider->quote($request) as $quote) {
            if ($cheapest === null || $quote->cost->lessThan($cheapest->cost)) {
                $cheapest = $quote;
            }
        }
    } catch (ProviderException $e) {
        // این شرکت این مسیر را سرویس‌دهی نمی‌کند — رد شو
    }
}

echo "ارزان‌ترین: {$cheapest->providerName} — {$cheapest->cost->inToman()} تومان";
```

## ثبت با پس‌کرایه

```php
if ($provider instanceof SupportsCOD) {
    $booking = (new BookingRequest(
        origin: $o, destination: $d, parcel: $p,
        orderId: 'ORDER-123',
    ))->withCashOnDelivery(Amount::fromToman(250_000));

    $shipment = $provider->createShipment($booking);
}
```

## برنامه‌ریزی جمع‌آوری پیک

```php
if ($provider instanceof SupportsPickup) {
    $pickup = new PickupRequest(
        windowStart: new DateTimeImmutable('tomorrow 10:00'),
        windowEnd: new DateTimeImmutable('tomorrow 12:00'),
        instructions: 'در زدن کافی است، پلاک ۱۰۰ واحد ۳',
    );

    $shipment = $provider->schedulePickup($shipment->getId(), $pickup);
}
```

## دانلود برچسب ارسال

```php
if ($provider instanceof SupportsLabel) {
    $label = $provider->getLabel($shipment->getId());

    if ($label->hasBytes()) {
        file_put_contents("labels/{$shipment->getId()}.{$label->format}", $label->bytes);
    } elseif ($label->hasUrl()) {
        header('Location: ' . $label->url);
    }
}
```

## یافتن نزدیک‌ترین شعبه

```php
if ($provider instanceof SupportsBranches) {
    $branches = $provider->listBranches('تهران');

    foreach ($branches as $branch) {
        printf("%s — %s\n", $branch->name, $branch->address);
    }
}
```

## Polling تا تحویل

```php
use Eram\Ersal\Shipment\ShipmentStatus;

while (true) {
    $shipment = $provider->track($shipmentId);

    if ($shipment->getStatus()->isTerminal()) {
        break;
    }

    sleep(900); // ۱۵ دقیقه — محدودیت نرخ شرکت را رعایت کنید
}

if ($shipment->getStatus() === ShipmentStatus::Delivered) {
    $this->notifyCustomerOfDelivery($shipment);
}
```

## محاسبه وزن محاسبه‌پذیر

```php
$parcel = new Parcel(
    weightGrams: 500,             // سبک ولی حجیم
    lengthMm: 500, widthMm: 400, heightMm: 300,
);

echo $parcel->weightGrams;              // ۵۰۰
echo $parcel->volumetricWeightGrams();  // ۱۲,۰۰۰
echo $parcel->chargeableWeightGrams();  // ۱۲,۰۰۰ — بابت حجم شارژ می‌شوید
```

## ادغام با فریم‌ورک (Laravel)

```php
// config/services.php
return [
    'tipax' => ['token' => env('TIPAX_TOKEN')],
];

// در Service Provider
$this->app->singleton(Ersal::class, function () {
    return new Ersal(
        logger: new MonologAdapter($this->app['log']),
        eventDispatcher: new LaravelDispatcher($this->app['events']),
    );
});

// در Controller
public function ship(Request $request, Ersal $ersal)
{
    $provider = $ersal->create('tipax', new TipaxConfig(
        token: config('services.tipax.token'),
    ));
    // ...
}
```
