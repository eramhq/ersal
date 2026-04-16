# ماهکس (Mahex)

> شرکت حمل داخلی. REST API. پشتیبانی از pickup و پس‌کرایه.

## پیکربندی

```php
use Eram\Ersal\Provider\Mahex\MahexConfig;

$config = new MahexConfig(
    token: 'your-bearer-token',
    sandbox: false,
);
```

## قابلیت‌ها

- ✅ `ShippingInterface`
- ✅ `SupportsPickup`
- ✅ `SupportsCOD`
- ❌ بدون label، بدون شعب

## احراز هویت

هدر `Authorization: Bearer <token>`.

## نکات

- اگر به برچسب چاپی نیاز دارید، از تیپاکس یا چاپار استفاده کنید
- فیلدهای فعلی را با مستندات توسعه‌دهنده ماهکس تایید کنید
