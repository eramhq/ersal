# پایگان (Paygan)

> تحویل درون‌شهری / لحظه‌ای. REST API. پشتیبانی از پس‌کرایه.

## پیکربندی

```php
use Eram\Ersal\Provider\Paygan\PayganConfig;

$config = new PayganConfig(
    apiKey: 'your-api-key',
    sandbox: false,
);
```

## قابلیت‌ها

- ✅ `ShippingInterface`
- ✅ `SupportsCOD`
- ❌ بدون label، بدون pickup (در لحظه‌ای pickup ضمنی است)، بدون شعب

## احراز هویت

هدر `X-Api-Key`.

## نکات

- بهینه‌شده برای ارسال درون‌شهری یا مسافت کوتاه
- آدرس endpointها را با مستندات توسعه‌دهنده پایگان تایید کنید
