# Core Concepts

## Shipment Lifecycle

Every shipment in Ersal follows the same lifecycle, regardless of which provider you use:

```
Quote → Book → Track → (Cancel)
```

1. **Quote** — Price a shipment without creating it. Returns a list of `Quote` objects (possibly one per service level)
2. **Book** — Create the shipment; carrier returns a tracking code and shipment ID
3. **Track** — Pull current status + chronological event history
4. **Cancel** — Cancel if the provider and its state allow it

## Provider Abstraction

All providers implement `ShippingInterface`:

```php
interface ShippingInterface
{
    public function getName(): string;

    /** @return list<Quote> */
    public function quote(QuoteRequest $request): array;

    public function createShipment(BookingRequest $request): ShipmentInterface;
    public function track(ShipmentId $id): ShipmentInterface;
    public function cancel(ShipmentId $id): ShipmentInterface;
}
```

This means you can swap providers by changing a single string — the rest of your code stays identical.

## Optional Capabilities

Not every provider supports every feature. Capabilities are expressed as separate interfaces:

- **`SupportsLabel`** — Providers that can issue printable labels
- **`SupportsPickup`** — Providers that schedule a courier pickup
- **`SupportsBranches`** — Providers with physical drop-off branches
- **`SupportsCOD`** — Providers that accept cash-on-delivery on `BookingRequest`

Use `instanceof` checks to handle these:

```php
if ($provider instanceof SupportsLabel) {
    $label = $provider->getLabel($shipment->getId());
    file_put_contents('label.pdf', $label->bytes);
}

if ($provider instanceof SupportsCOD) {
    $booking = $booking->withCashOnDelivery(Amount::fromToman(250_000));
}
```

## Status Model

`ShipmentStatus` is an enum with 10 cases:

| Case | Terminal? | Meaning |
|------|-----------|---------|
| `Draft` | no | Constructed locally, not yet submitted |
| `Quoted` | no | Priced, not booked |
| `Booked` | no | Accepted by the carrier |
| `PickedUp` | no | Collected by the courier |
| `InTransit` | no | Moving through the carrier network |
| `OutForDelivery` | no | Final leg — courier heading to the recipient |
| `Delivered` | **yes** | Successfully delivered |
| `Failed` | **yes** | Undeliverable |
| `Returned` | **yes** | Returned to sender |
| `Cancelled` | **yes** | Cancelled before delivery |

Use `ShipmentStatus::isTerminal()` to check whether polling can stop.

## Units (No Floats)

To eliminate floating-point drift, Ersal uses integers everywhere:

- `Parcel::$weightGrams` — grams (int)
- `Parcel::$lengthMm`, `$widthMm`, `$heightMm` — millimeters (int)
- `Amount` — Rials (int)

Each provider converts to its own expected unit internally. You never multiply or divide for unit conversion.

## Immutability

All value objects and DTOs in Ersal are immutable:

- `Amount` — arithmetic returns new instances
- `Shipment` — `withStatus()`, `withHistory()`, `withCost()` return new instances
- `Address`, `Parcel`, `Quote`, `QuoteRequest`, `BookingRequest` — set once at construction

This prevents accidental mutation bugs where a shared reference changes state unexpectedly.

## Dependency Injection

The `Ersal` constructor accepts four optional dependencies:

```php
$ersal = new Ersal(
    httpClient: $myHttpClient,          // Custom HTTP transport
    logger: $myLogger,                  // Debug logging
    eventDispatcher: $myDispatcher,     // Lifecycle events
    soapFactory: $mySoapFactory,        // Custom SOAP client creation
);
```

All parameters are optional. Defaults use `ext-curl` and `ext-soap` directly — no Guzzle, no Symfony HttpClient, no framework coupling.

## SOAP vs REST

Ersal supports both SOAP-based providers (Iran Post domestic services) and REST-based providers (everyone else). The distinction is invisible to your code — both implement `ShippingInterface`.
