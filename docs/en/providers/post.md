# Iran Post (شرکت پست)

> National postal service. SOAP-based API. Supports label and branches.

## Configuration

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

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `username` | `string` | Yes | — | API username |
| `password` | `string` | Yes | — | API password |
| `contractCode` | `string` | Yes | — | Merchant contract identifier |
| `sandbox` | `bool` | No | `false` | Use sandbox WSDL |
| `wsdlUrl` | `?string` | No | `null` | Override WSDL endpoint |

## Capabilities

| Interface | Supported |
|-----------|-----------|
| `ShippingInterface` | ✅ |
| `SupportsLabel` | ✅ |
| `SupportsBranches` | ✅ |
| `SupportsPickup` | ❌ |
| `SupportsCOD` | ❌ |

## Setup

```php
use Eram\Ersal\Ersal;
use Eram\Ersal\Provider\Post\PostConfig;

$ersal = new Ersal();
$post = $ersal->create('post', new PostConfig(
    username: 'u', password: 'p', contractCode: 'c',
));
```

## Service levels

Iran Post's most common levels, passed as `serviceLevel`:

| Value | Persian | Description |
|-------|---------|-------------|
| `pishtaz` | پیشتاز | Priority domestic |
| `sefareshi` | سفارشی | Registered (standard) |
| `special` | ویژه | Premium domestic |
| `international` | بین‌الملل | International parcels |

## quote() — price a shipment

```php
use Eram\Ersal\Address\Address;
use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Request\QuoteRequest;

$origin = new Address(
    firstName: 'Sender', lastName: 'Name',
    phone: '09123456789',
    province: 'تهران', city: 'تهران',
    addressLine: 'خیابان انقلاب، پلاک 1',
    postalCode: '1234567890',
);

$destination = new Address(
    firstName: 'Receiver', lastName: 'Name',
    phone: '09121111111',
    province: 'فارس', city: 'شیراز',
    addressLine: 'خیابان زند، پلاک 50',
    postalCode: '7145678901',
);

$parcel = new Parcel(weightGrams: 2000, lengthMm: 400, widthMm: 300, heightMm: 100);

$quotes = $post->quote(new QuoteRequest(
    origin: $origin,
    destination: $destination,
    parcel: $parcel,
    serviceLevel: 'pishtaz',
));

foreach ($quotes as $quote) {
    echo "{$quote->serviceLevel}: {$quote->cost->inToman()} Toman\n";
}
```

## createShipment()

```php
use Eram\Ersal\Request\BookingRequest;

$shipment = $post->createShipment(new BookingRequest(
    origin: $origin,
    destination: $destination,
    parcel: $parcel,
    orderId: 'ORDER-POST-001',
    serviceLevel: 'pishtaz',
    description: 'Book order',
));

echo $shipment->getTrackingCode();  // e.g. 'RB123456789IR'
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
    // Iran Post only allows cancellation pre-pickup
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
    $branches = $post->listBranches('اصفهان');  // null = all cities

    foreach ($branches as $branch) {
        printf(
            "%s — %s (lat: %s, lng: %s)\n",
            $branch->name, $branch->address,
            $branch->lat, $branch->lng,
        );
    }
}
```

## Error handling

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

## Notes

- Uses the native `ext-soap` client — no Composer SOAP library
- WSDL is cached by default (`cache_wsdl: true`)
- Verify current WSDL URL and method signatures with Iran Post's published API catalog
- COD and pickup are not exposed through the standard SOAP contract; use Tipax or Chapar for those
