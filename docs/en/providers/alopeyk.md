# Alopeyk (الوپیک)

> On-demand urban courier. REST API. Supports pickup scheduling.

## Configuration

```php
use Eram\Ersal\Provider\Alopeyk\AlopeykConfig;

$config = new AlopeykConfig(
    token: 'your-bearer-token',
    sandbox: false,
    baseUrl: null,
);
```

## Capabilities

| Interface | Supported |
|-----------|-----------|
| `ShippingInterface` | ✅ |
| `SupportsPickup` | ✅ |
| `SupportsLabel` | ❌ |
| `SupportsBranches` | ❌ |
| `SupportsCOD` | ❌ |

## Setup

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Alopeyk\AlopeykConfig;

$alopeyk = (new Ersal())->create('alopeyk', new AlopeykConfig(token: 'your-token'));
```

Alopeyk uses `Authorization: Bearer <token>`.

## quote()

Optimized for same-city runs — use `ServiceLevel::SameDay` (value `'same_day'`).

```php
use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Catalog\ServiceLevel;
use Eram\Ersal\Request\QuoteRequest;

$origin = new Address(
    firstName: 'Sender', lastName: 'N',
    phone: '09123456789',
    province: 'تهران', city: 'تهران',
    addressLine: 'خیابان آزادی',
    lat: 35.7000, lng: 51.3500,
);

$destination = new Address(
    firstName: 'Receiver', lastName: 'N',
    phone: '09121111111',
    province: 'تهران', city: 'تهران',
    addressLine: 'خیابان ولیعصر',
    lat: 35.7300, lng: 51.4100,
);

$quotes = $alopeyk->quote(new QuoteRequest(
    origin: $origin, destination: $destination,
    parcel: new Parcel(weightGrams: 500),
    serviceLevel: ServiceLevel::SameDay->value,
));

foreach ($quotes as $quote) {
    echo "{$quote->cost->inToman()} Toman\n";
}
```

## createShipment()

```php
use Eram\Ersal\Request\BookingRequest;

$shipment = $alopeyk->createShipment(new BookingRequest(
    origin: $origin, destination: $destination,
    parcel: new Parcel(weightGrams: 500),
    orderId: 'ORDER-AL-001',
    serviceLevel: 'same_day',
));
```

## track()

```php
$tracked = $alopeyk->track($shipment->getId());

echo $tracked->getStatus()->label('fa');

// Courier location typically arrives in event.raw for on-demand carriers
foreach ($tracked->getHistory() as $event) {
    $courierLocation = $event->raw['courier_location'] ?? null;
    printf("[%s] %s\n", $event->at->format('H:i'), $event->description);
}
```

## cancel()

```php
use Eram\Ersal\Exception\CancellationException;

try {
    $cancelled = $alopeyk->cancel($shipment->getId());
} catch (CancellationException $e) {
    // Courier already dispatched — cancellation denied
}
```

## schedulePickup()

```php
use Eram\Ersal\Contracts\SupportsPickup;
use Eram\Ersal\Request\PickupRequest;

if ($alopeyk instanceof SupportsPickup) {
    $alopeyk->schedulePickup($shipment->getId(), new PickupRequest(
        windowStart: new DateTimeImmutable('today 14:00'),
        windowEnd: new DateTimeImmutable('today 14:30'),
        instructions: 'پشت در آپارتمان، زنگ نزنید',
    ));
}
```

## Error handling

```php
use Eram\Ersal\Exception\ProviderException;
use Eram\Ersal\Provider\Alopeyk\AlopeykErrorCode;

try {
    $alopeyk->createShipment($booking);
} catch (ProviderException $e) {
    $code = AlopeykErrorCode::tryFrom((int) $e->getErrorCode());

    if ($code === AlopeykErrorCode::NoCourierAvailable) {
        // Retry in a few minutes
    }

    echo $code?->message('en') ?? $e->getMessage();
}
```

## Notes

- Optimized for intra-city same-day runs
- Geo coordinates on addresses are strongly recommended
- No COD, no label export
- Verify endpoint paths with Alopeyk's developer portal
