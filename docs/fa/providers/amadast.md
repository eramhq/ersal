# آمادست (Amadast)

> تجمیع‌کننده چند شرکتی / کارگزار last-mile. REST API.

## پیکربندی

```php
use Eram\Ersal\Provider\Amadast\AmadastConfig;

$config = new AmadastConfig(
    apiKey: 'your-api-key',
    sandbox: false,
);
```

## قابلیت‌ها

- ✅ `ShippingInterface`
- ❌ بدون label/pickup/شعب/COD در سطح تجمیع‌کننده — این‌ها به شرکت حملی که آمادست انتخاب می‌کند واگذار می‌شوند

## احراز هویت

هدر `X-Api-Key`.

## نکات

آمادست در یک فراخوان `quote()` چند پیشنهاد از شرکت‌های مختلف برمی‌گرداند. از فیلد `Quote::$extra` برای دیدن اینکه هر پیشنهاد به کدام شرکت تعلق دارد استفاده کنید.
