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

## Capabilities

- ✅ `ShippingInterface`
- ✅ `SupportsLabel`
- ✅ `SupportsPickup`
- ✅ `SupportsCOD`
- ❌ No branches

## Auth

Uses `X-Api-Key` header.

## Notes

- Costs in Rials, weight in grams, dimensions in millimeters
- Verify endpoint paths with Chapar's developer portal
