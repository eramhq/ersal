# Cookbook

## Compare multiple carriers

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
        // this carrier doesn't service the route — skip
    }
}

echo "Cheapest: {$cheapest->providerName} — {$cheapest->cost->inToman()} Toman";
```

## Book with cash-on-delivery

```php
if ($provider instanceof SupportsCOD) {
    $booking = (new BookingRequest(
        origin: $o,
        destination: $d,
        parcel: $p,
        orderId: 'ORDER-123',
    ))->withCashOnDelivery(Amount::fromToman(250_000));

    $shipment = $provider->createShipment($booking);
}
```

## Schedule a pickup

```php
if ($provider instanceof SupportsPickup) {
    $pickup = new PickupRequest(
        windowStart: new DateTimeImmutable('tomorrow 10:00'),
        windowEnd: new DateTimeImmutable('tomorrow 12:00'),
        instructions: 'در زدن کافی است، پلاک 100 واحد 3',
    );

    $shipment = $provider->schedulePickup($shipment->getId(), $pickup);
}
```

## Download a label

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

## Find the nearest branch

```php
if ($provider instanceof SupportsBranches) {
    $branches = $provider->listBranches('تهران');

    foreach ($branches as $branch) {
        printf("%s — %s\n", $branch->name, $branch->address);
    }
}
```

## Poll until delivered

```php
use Eram\Ersal\Shipment\ShipmentStatus;

while (true) {
    $shipment = $provider->track($shipmentId);

    if ($shipment->getStatus()->isTerminal()) {
        break;
    }

    sleep(900); // 15 minutes — respect carrier rate limits
}

if ($shipment->getStatus() === ShipmentStatus::Delivered) {
    $this->notifyCustomerOfDelivery($shipment);
}
```

## Compute chargeable weight

```php
$parcel = new Parcel(
    weightGrams: 500,             // light but bulky
    lengthMm: 500, widthMm: 400, heightMm: 300,
);

echo $parcel->weightGrams;              // 500
echo $parcel->volumetricWeightGrams();  // 12,000
echo $parcel->chargeableWeightGrams();  // 12,000 — you'll be billed for the bulk
```

## Framework integration (Laravel example)

```php
// config/services.php
return [
    'tipax' => ['token' => env('TIPAX_TOKEN')],
];

// In a service provider
$this->app->singleton(Ersal::class, function () {
    return new Ersal(
        logger: new MonologAdapter($this->app['log']),
        eventDispatcher: new LaravelDispatcher($this->app['events']),
    );
});

// In a controller
public function ship(Request $request, Ersal $ersal)
{
    $provider = $ersal->create('tipax', new TipaxConfig(
        token: config('services.tipax.token'),
    ));
    // ...
}
```
