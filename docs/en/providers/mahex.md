# Mahex (ماهکس)

> Domestic courier. REST API. Supports pickup and cash-on-delivery.

## Configuration

```php
use Eram\Ersal\Provider\Mahex\MahexConfig;

$config = new MahexConfig(
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
| `SupportsCOD` | ✅ |
| `SupportsLabel` | ❌ |
| `SupportsBranches` | ❌ |

## Setup

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Mahex\MahexConfig;

$mahex = (new Ersal())->create('mahex', new MahexConfig(token: 'your-token'));
```

Mahex uses `Authorization: Bearer <token>`.

## quote()

```php
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Request\QuoteRequest;

$quotes = $mahex->quote(new QuoteRequest(
    origin: $origin,
    destination: $destination,
    parcel: new Parcel(weightGrams: 2500),
));

foreach ($quotes as $quote) {
    echo "{$quote->serviceLevel}: {$quote->cost->inToman()} Toman\n";
}
```

## createShipment()

```php
use Eram\Ersal\Request\BookingRequest;

$shipment = $mahex->createShipment(new BookingRequest(
    origin: $origin,
    destination: $destination,
    parcel: $parcel,
    orderId: 'ORDER-MHX-001',
    serviceLevel: 'standard',
));
```

## Cash-on-delivery

```php
use Eram\Ersal\Contracts\SupportsCOD;
use Eram\Ersal\Money\Amount;

if ($mahex instanceof SupportsCOD) {
    $booking = (new BookingRequest(
        origin: $origin, destination: $destination, parcel: $parcel,
        orderId: 'ORDER-MHX-COD-001',
    ))->withCashOnDelivery(Amount::fromToman(800_000));

    $shipment = $mahex->createShipment($booking);
}
```

## track()

```php
$tracked = $mahex->track($shipment->getId());
echo $tracked->getStatus()->label('fa');
```

## cancel()

```php
$cancelled = $mahex->cancel($shipment->getId());
```

## schedulePickup()

```php
use Eram\Ersal\Contracts\SupportsPickup;
use Eram\Ersal\Request\PickupRequest;

if ($mahex instanceof SupportsPickup) {
    $mahex->schedulePickup($shipment->getId(), new PickupRequest(
        windowStart: new DateTimeImmutable('tomorrow 14:00'),
        windowEnd: new DateTimeImmutable('tomorrow 16:00'),
        instructions: 'تماس قبل از حرکت',
    ));
}
```

## Error handling

```php
use Eram\Ersal\Exception\ProviderException;
use Eram\Ersal\Provider\Mahex\MahexErrorCode;

try {
    $mahex->createShipment($booking);
} catch (ProviderException $e) {
    $code = MahexErrorCode::tryFrom((int) $e->getErrorCode());
    echo $code?->message('en') ?? $e->getMessage();
}
```

## Notes

- No label endpoint — if you need a printed label, route via Tipax or Chapar instead
- Verify current field names with Mahex's developer portal
