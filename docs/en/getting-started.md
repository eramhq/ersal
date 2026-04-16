# Getting Started

## Requirements

- PHP 8.1 or later
- Extensions: `ext-curl`, `ext-json`, `ext-openssl`, `ext-soap`

## Installation

```bash
composer require eram/ersal
```

Ersal has **zero** Composer dependencies. It only relies on PHP extensions that ship with most PHP installations.

## Quick Example

Here is a complete shipping flow using Tipax:

### 1. Create a Provider

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Tipax\TipaxConfig;

$ersal = new Ersal();
$provider = $ersal->create('tipax', new TipaxConfig(
    token: 'your-api-token',
));
```

### 2. Get a Quote

```php
use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Request\QuoteRequest;

$origin = new Address(
    firstName: 'Shop',
    lastName: 'Owner',
    phone: '09123456789',
    province: 'تهران',
    city: 'تهران',
    addressLine: 'خیابان ولیعصر، پلاک 100',
    postalCode: '1234567890',
);

$destination = new Address(
    firstName: 'Customer',
    lastName: 'Name',
    phone: '09121111111',
    province: 'اصفهان',
    city: 'اصفهان',
    addressLine: 'خیابان چهارباغ، پلاک 20',
    postalCode: '8159876543',
);

$parcel = new Parcel(
    weightGrams: 1500,           // 1.5 kg
    lengthMm: 300, widthMm: 200, heightMm: 100,
);

$quotes = $provider->quote(new QuoteRequest(
    origin: $origin,
    destination: $destination,
    parcel: $parcel,
));

foreach ($quotes as $quote) {
    echo "{$quote->serviceLevel}: {$quote->cost->inToman()} Toman (ETA {$quote->etaDays}d)\n";
}
```

### 3. Book the Shipment

```php
use Eram\Ersal\Request\BookingRequest;

$shipment = $provider->createShipment(new BookingRequest(
    origin: $origin,
    destination: $destination,
    parcel: $parcel,
    orderId: 'ORDER-2026-0001',
    serviceLevel: 'standard',
    quoteId: $quotes[0]->quoteId, // reuse the price
));

echo $shipment->getTrackingCode();  // show to customer
echo $shipment->getStatus()->value; // 'booked'
```

### 4. Track the Shipment

```php
use Eram\Ersal\Shipment\ShipmentId;

$tracked = $provider->track(new ShipmentId($shipment->getId()->value()));

foreach ($tracked->getHistory() as $event) {
    echo $event->at->format('Y-m-d H:i');
    echo ' — ' . $event->status->label('en');
    echo ' — ' . $event->description . "\n";
}
```

### 5. Cancel (if needed)

```php
$cancelled = $provider->cancel($shipment->getId());
// $cancelled->getStatus() === ShipmentStatus::Cancelled
```

## Supported Providers

| Alias | Carrier | Protocol | Capabilities |
|-------|---------|----------|--------------|
| `post` | Iran Post | SOAP | Label, Branches |
| `tipax` | Tipax | REST | Label, Pickup, Branches, COD |
| `chapar` | Chapar | REST | Label, Pickup, COD |
| `mahex` | Mahex | REST | Pickup, COD |
| `amadast` | Amadast | REST | — |
| `paygan` | Paygan | REST | COD |
| `alopeyk` | Alopeyk | REST | Pickup |

## Next Steps

- [Core Concepts](concepts.md) — Understand the design
- [Cookbook](cookbook.md) — Real-world recipes
- [Provider Catalog](README.md#provider-catalog) — Per-carrier documentation
