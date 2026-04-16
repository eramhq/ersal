# چاپار (Chapar Express)

> شرکت حمل خصوصی. REST API. پشتیبانی از برچسب، pickup و پس‌کرایه.

## پیکربندی

```php
use Eram\Ersal\Provider\Chapar\ChaparConfig;

$config = new ChaparConfig(
    apiKey: 'your-api-key',
    sandbox: false,
);
```

## قابلیت‌ها

- ✅ `ShippingInterface`
- ✅ `SupportsLabel`
- ✅ `SupportsPickup`
- ✅ `SupportsCOD`
- ❌ بدون شعب

## احراز هویت

هدر `X-Api-Key`.

## نکات

- هزینه به ریال، وزن به گرم، ابعاد به میلی‌متر
- آدرس endpointها را با مستندات توسعه‌دهنده چاپار تایید کنید
