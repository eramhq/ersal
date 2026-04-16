# Paygan (پایگان)

> Same-city / on-demand delivery. REST API. Supports cash-on-delivery.

## Configuration

```php
use Eram\Ersal\Provider\Paygan\PayganConfig;

$config = new PayganConfig(
    apiKey: 'your-api-key',
    sandbox: false,
    baseUrl: null,
);
```

## Capabilities

| Interface | Supported |
|-----------|-----------|
| `ShippingInterface` | ✅ |
| `SupportsCOD` | ✅ |
| `SupportsLabel` | ❌ |
| `SupportsPickup` | ❌ (implicit — on-demand) |
| `SupportsBranches` | ❌ |

## Setup

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Paygan\PayganConfig;

$paygan = (new Ersal())->create('paygan', new PayganConfig(apiKey: 'your-key'));
```

Paygan uses `X-Api-Key` header authentication.

## quote()

Pricing depends heavily on distance — geo coordinates on addresses improve accuracy.

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
    echo "{$quote->cost->inToman()} Toman ({$quote->etaDays}d ETA)\n";
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

## Cash-on-delivery

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

## Error handling

```php
use Eram\Ersal\Exception\ProviderException;
use Eram\Ersal\Provider\Paygan\PayganErrorCode;

try {
    $paygan->createShipment($booking);
} catch (ProviderException $e) {
    $code = PayganErrorCode::tryFrom((int) $e->getErrorCode());
    echo $code?->message('en') ?? $e->getMessage();
}
```

## Notes

- Designed for same-city or short-range deliveries
- Pickup is implicit (on-demand) — there is no separate pickup-scheduling step
- Verify endpoint paths with Paygan's developer portal
