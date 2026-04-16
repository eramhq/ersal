# Chapar Express

> Private courier. REST API. Supports label, pickup, cash-on-delivery.

## Configuration

```php
use Eram\Ersal\Provider\Chapar\ChaparConfig;

$config = new ChaparConfig(
    apiKey: 'your-api-key',
    sandbox: false,
    baseUrl: null,
);
```

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `apiKey` | `string` | Yes | — | API key from Chapar portal |
| `sandbox` | `bool` | No | `false` | Use sandbox environment |
| `baseUrl` | `?string` | No | `null` | Override API host |

## Capabilities

| Interface | Supported |
|-----------|-----------|
| `ShippingInterface` | ✅ |
| `SupportsLabel` | ✅ |
| `SupportsPickup` | ✅ |
| `SupportsCOD` | ✅ |
| `SupportsBranches` | ❌ |

## Setup

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Chapar\ChaparConfig;

$chapar = (new Ersal())->create('chapar', new ChaparConfig(apiKey: 'your-key'));
```

Chapar uses `X-Api-Key` header authentication.

## quote()

```php
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Request\QuoteRequest;

$quotes = $chapar->quote(new QuoteRequest(
    origin: $origin,
    destination: $destination,
    parcel: new Parcel(weightGrams: 1000, lengthMm: 200, widthMm: 150, heightMm: 80),
));

foreach ($quotes as $quote) {
    printf("%s — %s Toman, ETA %sd\n",
        $quote->serviceLevel, $quote->cost->inToman(), $quote->etaDays);
}
```

## createShipment()

```php
use Eram\Ersal\Request\BookingRequest;

$shipment = $chapar->createShipment(new BookingRequest(
    origin: $origin,
    destination: $destination,
    parcel: $parcel,
    orderId: 'ORDER-CHP-001',
    serviceLevel: 'standard',
    quoteId: $quotes[0]->quoteId,
    description: 'Electronics',
));
```

## Cash-on-delivery

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

## Error handling

```php
use Eram\Ersal\Exception\ProviderException;
use Eram\Ersal\Provider\Chapar\ChaparErrorCode;

try {
    $chapar->createShipment($booking);
} catch (ProviderException $e) {
    $code = ChaparErrorCode::tryFrom((int) $e->getErrorCode());
    echo $code?->message('en') ?? $e->getMessage();
}
```

## Notes

- Costs in Rials, weight in grams, dimensions in millimeters
- Verify endpoint paths with Chapar's developer portal
