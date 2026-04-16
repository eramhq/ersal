# مفاهیم اصلی

## چرخه حیات مرسوله

هر مرسوله در ارسال از چرخه یکسانی پیروی می‌کند، فارغ از اینکه کدام provider را انتخاب کنید:

```
Quote (استعلام) → Book (ثبت) → Track (ردیابی) → (Cancel — لغو)
```

۱. **Quote** — قیمت‌گذاری مرسوله بدون ثبت. لیستی از `Quote` برمی‌گرداند (گاهی یکی به ازای هر سطح سرویس)
۲. **Book** — ایجاد مرسوله؛ کد رهگیری و شناسه مرسوله از طرف شرکت حمل دریافت می‌شود
۳. **Track** — دریافت وضعیت فعلی + تاریخچه زمانی رویدادها
۴. **Cancel** — لغو در صورت مجاز بودن توسط شرکت حمل و وضعیت مرسوله

## انتزاع Provider

تمام providerها `ShippingInterface` را پیاده‌سازی می‌کنند. با تعویض یک رشته می‌توانید provider را عوض کنید — باقی کد شما دست نخورده می‌ماند.

## قابلیت‌های اختیاری

- **`SupportsLabel`** — providerهایی که برچسب قابل چاپ می‌دهند
- **`SupportsPickup`** — providerهایی که جمع‌آوری از مبدأ را برنامه‌ریزی می‌کنند
- **`SupportsBranches`** — providerهایی که شعب فیزیکی دارند
- **`SupportsCOD`** — providerهایی که پس‌کرایه (COD) را می‌پذیرند

```php
if ($provider instanceof SupportsCOD) {
    $booking = $booking->withCashOnDelivery(Amount::fromToman(250_000));
}
```

## مدل وضعیت

`ShipmentStatus` یک enum با ۱۰ حالت است:

| وضعیت | پایانی؟ | معنا |
|-------|---------|------|
| `Draft` | خیر | ساخته شده ولی هنوز ارسال نشده |
| `Quoted` | خیر | قیمت‌گذاری شده، ثبت نشده |
| `Booked` | خیر | پذیرش توسط شرکت حمل |
| `PickedUp` | خیر | جمع‌آوری توسط پیک |
| `InTransit` | خیر | در حال حرکت در شبکه |
| `OutForDelivery` | خیر | در حال تحویل نهایی |
| `Delivered` | **بله** | تحویل موفق |
| `Failed` | **بله** | تحویل‌ناپذیر |
| `Returned` | **بله** | بازگشت به مبدأ |
| `Cancelled` | **بله** | لغو شده |

`ShipmentStatus::isTerminal()` می‌گوید آیا polling باید متوقف شود یا نه.

## واحدها (بدون اعداد اعشاری)

برای حذف خطای ممیز شناور:

- `Parcel::$weightGrams` — گرم (int)
- `Parcel::$lengthMm/widthMm/heightMm` — میلی‌متر (int)
- `Amount` — ریال (int)

هر provider خودش واحدها را به شکل مورد نیاز API تبدیل می‌کند.

## تغییرناپذیری (Immutability)

تمام value objectها و DTOها تغییرناپذیرند. `withStatus()`، `withHistory()`، `withCost()` نسخه‌های جدید می‌سازند — نسخه اصلی ثابت می‌ماند.

## تزریق وابستگی

```php
$ersal = new Ersal(
    httpClient: $myHttpClient,
    logger: $myLogger,
    eventDispatcher: $myDispatcher,
    soapFactory: $mySoapFactory,
);
```

همه اختیاری‌اند. پیش‌فرض از `ext-curl` و `ext-soap` استفاده می‌کند.

## SOAP در مقابل REST

ارسال هر دو را پشتیبانی می‌کند: providerهای SOAP (پست ایران) و REST (بقیه). تمایز از دید کد شما نامرئی است — همه `ShippingInterface` را پیاده‌سازی می‌کنند.
