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

- ✅ `ShippingInterface`
- ✅ `SupportsCOD`
- ❌ No label, no pickup (on-demand — pickup is implicit), no branches

## Auth

Uses `X-Api-Key` header.

## Notes

- Designed for same-city or short-range deliveries
- Verify endpoint paths with Paygan's developer portal
