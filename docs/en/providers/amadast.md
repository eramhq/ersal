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

- ✅ `ShippingInterface`
- ❌ No label / pickup / branches / COD at the aggregator level — those are provided by the underlying carrier Amadast routes to

## Auth

Uses `X-Api-Key` header.

## Notes

Amadast returns multiple offers from different underlying carriers in a single `quote()` call. Use the `Quote::$extra` field to see which carrier each offer maps to.
