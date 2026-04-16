# مدیریت خطا

## سلسله‌مراتب استثنا

```
RuntimeException
└── ErsalException
    ├── ConnectionException        # خطای transport / TLS / JSON decode
    ├── InvalidAddressException    # تلفن / کد پستی / فیلد اجباری نامعتبر
    ├── InvalidParcelException     # وزن یا ابعاد غیرمثبت
    ├── InvalidAmountException     # مقدار ریال منفی
    └── ProviderException          # شرکت خطا برگرداند
        ├── BookingException       # createShipment شکست خورد
        ├── TrackingException      # هدف track() یافت نشد
        └── CancellationException  # cancel() توسط وضعیت شرکت رد شد
```

همه استثناهای ارسال از `ErsalException` ارث می‌برند. یک `catch (ErsalException $e)` همه را پوشش می‌دهد.

## ProviderException

توسط هر عملیات provider وقتی شرکت خطا برگرداند پرتاب می‌شود. دسترسی‌ها:

```php
$e->getProviderName();   // 'tipax'، 'post'، ...
$e->getErrorCode();      // int|string — مختص شرکت
$e->getMessage();        // پیام بومی‌شده (فارسی به صورت پیش‌فرض)
```

subclass دقیق می‌گوید کدام عملیات شکست خورده:

```php
try {
    $provider->createShipment($request);
} catch (BookingException $e) {
    // مشکل در ثبت
} catch (ConnectionException $e) {
    // شبکه/transport — تلاش مجدد امن است
} catch (ProviderException $e) {
    // سایر خطاهای شرکت
}
```

## استراتژی retry

- **`ConnectionException`** — خطای transport گذرا. با backoff دوباره تلاش کنید.
- **`TrackingException` با کد ۴۰۴** — مرسوله یافت نشد؛ retry نکنید.
- **`BookingException` با کد محدودیت نرخ** — صبر کنید و بعد retry کنید.
- **سایر `ProviderException`** — معمولاً دائمی؛ log کنید و به اپراتور نمایش دهید.

## کدهای خطای هر provider

هر provider enum کد خطای اختصاصی با پیام‌های دوزبانه دارد:

```php
use Eram\Ersal\Provider\Tipax\TipaxErrorCode;

try {
    $provider->createShipment($request);
} catch (BookingException $e) {
    $code = TipaxErrorCode::tryFrom($e->getErrorCode());
    echo $code?->message('fa') ?? $e->getMessage();
}
```

Enumهای مختص هر provider:

- `Eram\Ersal\Provider\Post\PostErrorCode`
- `Eram\Ersal\Provider\Tipax\TipaxErrorCode`
- `Eram\Ersal\Provider\Chapar\ChaparErrorCode`
- `Eram\Ersal\Provider\Mahex\MahexErrorCode`
- `Eram\Ersal\Provider\Amadast\AmadastErrorCode`
- `Eram\Ersal\Provider\Paygan\PayganErrorCode`
- `Eram\Ersal\Provider\Alopeyk\AlopeykErrorCode`

هر enum متد `message(string $locale = 'fa')` دارد — `'en'` برای انگلیسی.
