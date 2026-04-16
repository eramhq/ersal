# پست ایران

> سرویس پست ملی. API مبتنی بر SOAP. پشتیبانی از برچسب و شعب.

## پیکربندی

```php
use Eram\Ersal\Provider\Post\PostConfig;

$config = new PostConfig(
    username: 'api-username',
    password: 'api-password',
    contractCode: 'your-contract-code',
    sandbox: false,
);
```

## قابلیت‌ها

- ✅ `ShippingInterface`
- ✅ `SupportsLabel`
- ✅ `SupportsBranches`
- ❌ بدون pickup، بدون COD (در سطح سرویس SOAP داخلی)

## سطوح سرویس

رایج‌ترین‌ها: `pishtaz` (پیشتاز)، `sefareshi` (سفارشی)، `special` (ویژه)، `international` (بین‌الملل). به صورت `serviceLevel` پاس دهید.

## نکات

- از `ext-soap` native استفاده می‌کند — بدون وابستگی Composer
- WSDL به صورت پیش‌فرض cache می‌شود
- آدرس WSDL و امضای متدها را با کاتالوگ API منتشر شده پست ایران تایید کنید
