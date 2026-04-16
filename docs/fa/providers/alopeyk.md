# الوپیک (Alopeyk)

> پیک لحظه‌ای شهری. REST API. پشتیبانی از برنامه‌ریزی pickup.

## پیکربندی

```php
use Eram\Ersal\Provider\Alopeyk\AlopeykConfig;

$config = new AlopeykConfig(
    token: 'your-bearer-token',
    sandbox: false,
);
```

## قابلیت‌ها

- ✅ `ShippingInterface`
- ✅ `SupportsPickup`
- ❌ بدون label، بدون شعب، بدون COD

## احراز هویت

هدر `Authorization: Bearer <token>`.

## نکات

- بهینه‌شده برای ارسال همان روز درون‌شهری
- از `ServiceLevel::SameDay` به عنوان سطح سرویس کانونی استفاده کنید
- آدرس endpointها را با مستندات توسعه‌دهنده الوپیک تایید کنید
