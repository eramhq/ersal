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

- ✅ `ShippingInterface`
- ✅ `SupportsPickup`
- ✅ `SupportsCOD`
- ❌ No label export, no branches

## Auth

Uses `Authorization: Bearer <token>`.

## Notes

- No label endpoint — if you need a printed label, route the shipment via Tipax or Chapar
- Verify current field names with Mahex's developer portal
