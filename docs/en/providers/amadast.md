# Amadast (آمادست)

> Cross-carrier aggregator / last-mile broker. REST API.

## Configuration

```php
use Eram\Ersal\Provider\Amadast\AmadastConfig;

$config = new AmadastConfig(
    apiKey: 'your-api-key',
    sandbox: false,
    baseUrl: null,
);
```

## Capabilities

| Interface | Supported |
|-----------|-----------|
| `ShippingInterface` | ✅ |
| `SupportsLabel` | ❌ |
| `SupportsPickup` | ❌ |
| `SupportsBranches` | ❌ |
| `SupportsCOD` | ❌ |

Amadast aggregates offers across carriers — label/pickup/COD are handled by whichever carrier Amadast routes your shipment to, not by Amadast itself.

## Setup

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Amadast\AmadastConfig;

$amadast = (new Ersal())->create('amadast', new AmadastConfig(apiKey: 'your-key'));
```

## quote() — compare carriers in one call

Amadast returns multiple offers from different underlying carriers. `Quote::$extra` typically carries the underlying carrier name.

```php
use Eram\Ersal\Request\QuoteRequest;

$offers = $amadast->quote(new QuoteRequest(
    origin: $origin,
    destination: $destination,
    parcel: $parcel,
));

foreach ($offers as $offer) {
    $carrier = $offer->extra['carrier'] ?? 'unknown';
    printf("%s via %s — %s Toman, ETA %sd (offer: %s)\n",
        $offer->serviceLevel, $carrier,
        $offer->cost->inToman(), $offer->etaDays, $offer->quoteId);
}

usort($offers, fn($a, $b) => $a->cost->inRials() <=> $b->cost->inRials());
$cheapest = $offers[0];
```

## createShipment() — book against a chosen offer

```php
use Eram\Ersal\Request\BookingRequest;

$shipment = $amadast->createShipment(new BookingRequest(
    origin: $origin,
    destination: $destination,
    parcel: $parcel,
    orderId: 'ORDER-AMD-001',
    quoteId: $cheapest->quoteId,
));
```

## track()

```php
$tracked = $amadast->track($shipment->getId());

foreach ($tracked->getHistory() as $event) {
    printf("[%s] %s — %s\n",
        $event->at->format('Y-m-d H:i'),
        $event->status->label('en'), $event->description);
}
```

## cancel()

```php
use Eram\Ersal\Exception\CancellationException;

try {
    $cancelled = $amadast->cancel($shipment->getId());
} catch (CancellationException $e) {
    // Underlying carrier rejected (already picked up, etc.)
}
```

## Error handling

```php
use Eram\Ersal\Exception\ProviderException;
use Eram\Ersal\Provider\Amadast\AmadastErrorCode;

try {
    $amadast->createShipment($booking);
} catch (ProviderException $e) {
    $code = AmadastErrorCode::tryFrom((int) $e->getErrorCode());
    echo $code?->message('en') ?? $e->getMessage();
}
```

## Notes

- Uses `X-Api-Key` header authentication
- When a quote doesn't include a `quoteId`, booking will re-quote at book time — price may drift
- Cancellation success depends on the underlying carrier Amadast routed to
