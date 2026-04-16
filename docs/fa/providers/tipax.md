# تیپاکس (Tipax)

> شرکت حمل خصوصی سراسری. REST API. پشتیبانی از label، pickup، شعب و پس‌کرایه.

## پیکربندی

```php
use Eram\Ersal\Provider\Tipax\TipaxConfig;

$config = new TipaxConfig(
    token: 'your-bearer-token',
    sandbox: false,
);
```

| پارامتر | نوع | اجباری | پیش‌فرض | توضیح |
|---------|-----|--------|---------|-------|
| `token` | `string` | بله | — | توکن Bearer از پرتال توسعه‌دهنده تیپاکس |
| `sandbox` | `bool` | خیر | `false` | محیط تست |
| `baseUrl` | `?string` | خیر | `null` | بازنویسی آدرس API |

## قابلیت‌ها

- ✅ `ShippingInterface` (quote, book, track, cancel)
- ✅ `SupportsLabel`
- ✅ `SupportsPickup`
- ✅ `SupportsBranches`
- ✅ `SupportsCOD`

## نکات

- هزینه به ریال ارسال و دریافت می‌شود
- وزن به گرم، ابعاد به میلی‌متر
- آدرس endpointها را با مستندات توسعه‌دهنده تیپاکس تایید کنید
