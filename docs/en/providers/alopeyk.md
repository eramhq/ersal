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

- ✅ `ShippingInterface`
- ✅ `SupportsPickup`
- ❌ No label, no branches, no COD

## Auth

Uses `Authorization: Bearer <token>`.

## Notes

- Optimized for intra-city same-day courier runs
- Use `ServiceLevel::SameDay` as the canonical service level
- Verify endpoint paths with Alopeyk's developer portal
