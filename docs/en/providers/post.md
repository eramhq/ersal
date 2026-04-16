# Iran Post (شرکت پست)

> National postal service. SOAP-based API. Supports label and branches.

## Configuration

```php
use Eram\Ersal\Provider\Post\PostConfig;

$config = new PostConfig(
    username: 'api-username',
    password: 'api-password',
    contractCode: 'your-contract-code',
    sandbox: false,
    wsdlUrl: null,   // optional override
);
```

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `username` | `string` | Yes | — | API username |
| `password` | `string` | Yes | — | API password |
| `contractCode` | `string` | Yes | — | Merchant contract identifier |
| `sandbox` | `bool` | No | `false` | Use sandbox WSDL |
| `wsdlUrl` | `?string` | No | `null` | Override WSDL endpoint |

## Capabilities

- ✅ `ShippingInterface`
- ✅ `SupportsLabel`
- ✅ `SupportsBranches`
- ❌ No pickup, no COD (domestic SOAP service tier)

## Service levels

Iran Post's most common levels: `pishtaz` (priority), `sefareshi` (registered), `special`, `international`. Pass as `QuoteRequest::$serviceLevel` / `BookingRequest::$serviceLevel`.

## Notes

- Uses the native `ext-soap` client — no Composer SOAP library
- WSDL is cached by default (`cache_wsdl: true`)
- Verify current WSDL URL and method signatures with Iran Post's published API catalog
