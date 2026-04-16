# Tipax

> Nationwide private courier. REST API. Supports label, pickup, branches, and cash-on-delivery.

## Configuration

```php
use Eram\Ersal\Provider\Tipax\TipaxConfig;

$config = new TipaxConfig(
    token: 'your-bearer-token',
    sandbox: false,
    baseUrl: null,    // optional override
);
```

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `token` | `string` | Yes | — | Bearer token from Tipax developer portal |
| `sandbox` | `bool` | No | `false` | Use sandbox environment |
| `baseUrl` | `?string` | No | `null` | Override the default API host |

## Capabilities

- ✅ `ShippingInterface` (quote, book, track, cancel)
- ✅ `SupportsLabel`
- ✅ `SupportsPickup`
- ✅ `SupportsBranches`
- ✅ `SupportsCOD`

## Notes

- Costs are sent and received in Rials
- Weight is sent in grams; dimensions in millimeters
- Verify current endpoint paths with Tipax's developer portal — the URLs in `TipaxProvider` reflect common REST conventions
