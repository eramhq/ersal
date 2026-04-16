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
    wsdlUrl: null,
);
```

| پارامتر | نوع | اجباری | پیش‌فرض | توضیح |
|---------|-----|--------|---------|-------|
| `username` | `string` | بله | — | نام کاربری API |
| `password` | `string` | بله | — | رمز عبور API |
| `contractCode` | `string` | بله | — | کد قرارداد پذیرنده |
| `sandbox` | `bool` | خیر | `false` | WSDL محیط تست |
| `wsdlUrl` | `?string` | خیر | `null` | بازنویسی آدرس WSDL |

## قابلیت‌ها

| رابط | پشتیبانی |
|------|---------|
| `ShippingInterface` | ✅ |
| `SupportsLabel` | ✅ |
| `SupportsBranches` | ✅ |
| `SupportsPickup` | ❌ |
| `SupportsCOD` | ❌ |

## راه‌اندازی

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Post\PostConfig;

$post = (new Ersal())->create('post', new PostConfig(
    username: 'u', password: 'p', contractCode: 'c',
));
```

## سطوح سرویس

| مقدار | فارسی | توضیح |
|-------|-------|-------|
| `pishtaz` | پیشتاز | داخلی اولویت‌دار |
| `sefareshi` | سفارشی | ثبت‌شده استاندارد |
| `special` | ویژه | داخلی پریمیوم |
| `international` | بین‌الملل | بسته بین‌المللی |

## quote()

```php
use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Request\QuoteRequest;

$origin = new Address(
    firstName: 'Sender', lastName: 'N',
    phone: '09123456789',
    province: 'تهران', city: 'تهران',
    addressLine: 'خیابان انقلاب، پلاک ۱',
    postalCode: '1234567890',
);

$destination = new Address(
    firstName: 'Receiver', lastName: 'N',
    phone: '09121111111',
    province: 'فارس', city: 'شیراز',
    addressLine: 'خیابان زند، پلاک ۵۰',
    postalCode: '7145678901',
);

$parcel = new Parcel(weightGrams: 2000, lengthMm: 400, widthMm: 300, heightMm: 100);

$quotes = $post->quote(new QuoteRequest(
    origin: $origin, destination: $destination, parcel: $parcel,
    serviceLevel: 'pishtaz',
));

foreach ($quotes as $quote) {
    echo "{$quote->serviceLevel}: {$quote->cost->inToman()} تومان\n";
}
```

## createShipment()

```php
use Eram\Ersal\Request\BookingRequest;

$shipment = $post->createShipment(new BookingRequest(
    origin: $origin, destination: $destination, parcel: $parcel,
    orderId: 'ORDER-POST-001',
    serviceLevel: 'pishtaz',
    description: 'کتاب',
));

echo $shipment->getTrackingCode();  // مثلاً 'RB123456789IR'
```

## track()

```php
$tracked = $post->track($shipment->getId());

echo $tracked->getStatus()->label('fa');

foreach ($tracked->getHistory() as $event) {
    printf("[%s] %s\n", $event->at->format('Y-m-d H:i'), $event->description);
}
```

## cancel()

```php
use Eram\Ersal\Exception\CancellationException;

try {
    $cancelled = $post->cancel($shipment->getId());
} catch (CancellationException $e) {
    // پست ایران فقط قبل از جمع‌آوری اجازه لغو می‌دهد
}
```

## getLabel()

```php
use Eram\Ersal\Contracts\SupportsLabel;

if ($post instanceof SupportsLabel) {
    $label = $post->getLabel($shipment->getId());
    file_put_contents("labels/post-{$shipment->getId()}.pdf", $label->bytes);
}
```

## listBranches()

```php
use Eram\Ersal\Contracts\SupportsBranches;

if ($post instanceof SupportsBranches) {
    $branches = $post->listBranches('اصفهان');

    foreach ($branches as $branch) {
        printf("%s — %s (lat: %s, lng: %s)\n",
            $branch->name, $branch->address,
            $branch->lat, $branch->lng);
    }
}
```

## مدیریت خطا

```php
use Eram\Ersal\Exception\ProviderException;
use Eram\Ersal\Provider\Post\PostErrorCode;

try {
    $post->createShipment($booking);
} catch (ProviderException $e) {
    $code = PostErrorCode::tryFrom((int) $e->getErrorCode());
    echo $code?->message('fa') ?? $e->getMessage();
}
```

## نکات

- از `ext-soap` native استفاده می‌کند — بدون وابستگی Composer
- WSDL به صورت پیش‌فرض cache می‌شود
- آدرس WSDL و امضای متدها را با کاتالوگ API منتشر شده پست ایران تایید کنید
- COD و pickup در قرارداد استاندارد SOAP عرضه نمی‌شوند؛ برای آن‌ها از تیپاکس یا چاپار استفاده کنید
