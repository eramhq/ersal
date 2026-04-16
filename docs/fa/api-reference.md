# مرجع API

مرجع کامل برای تمام کلاس‌ها، رابط‌ها و متدهای عمومی.

## Ersal (نقطه ورود)

```php
namespace Eram\Ersal;

final class Ersal
{
    public function __construct(
        ?HttpClient $httpClient = null,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
        ?SoapClientFactory $soapFactory = null,
    );

    public function create(string $provider, object $config): ShippingInterface;

    /** @return list<string> */
    public static function available(): array;
}
```

## Contracts

### ShippingInterface

```php
interface ShippingInterface
{
    public function getName(): string;

    /** @return list<Quote> */
    public function quote(QuoteRequest $request): array;

    public function createShipment(BookingRequest $request): ShipmentInterface;
    public function track(ShipmentId $id): ShipmentInterface;
    public function cancel(ShipmentId $id): ShipmentInterface;
}
```

### ShipmentInterface

```php
interface ShipmentInterface
{
    public function getId(): ShipmentId;
    public function getProviderName(): string;
    public function getTrackingCode(): string;
    public function getStatus(): ShipmentStatus;
    public function getOrigin(): Address;
    public function getDestination(): Address;
    public function getParcel(): Parcel;
    public function getCost(): ?Amount;

    /** @return list<TrackingEvent> */
    public function getHistory(): array;

    /** @return array<string, mixed> */
    public function getExtra(): array;

    public function withStatus(ShipmentStatus $status): static;

    /** @param list<TrackingEvent> $history */
    public function withHistory(array $history): static;
}
```

### رابط‌های قابلیت

```php
interface SupportsLabel
{
    public function getLabel(ShipmentId $id): LabelResponse;
}

interface SupportsPickup
{
    public function schedulePickup(ShipmentId $id, PickupRequest $request): ShipmentInterface;
}

interface SupportsBranches
{
    /** @return list<Branch> */
    public function listBranches(?string $city = null): array;
}

interface SupportsCOD {}   // marker interface
```

## Money

### Amount

```php
final class Amount
{
    public static function fromRials(int $rials): self;
    public static function fromToman(int $toman): self;

    public function inRials(): int;
    public function inToman(): int;

    public function add(self $other): self;
    public function subtract(self $other): self;
    public function equals(self $other): bool;
    public function greaterThan(self $other): bool;
    public function lessThan(self $other): bool;
    public function isZero(): bool;

    public function __toString(): string; // ریال
}
```

## Address و Parcel

### Address

```php
final class Address
{
    public readonly string $phone;       // نرمال‌شده به +989xxxxxxxxx

    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        string $phone,                   // قالب‌های 09xx، +98، 0098
        public readonly string $province,
        public readonly string $city,
        public readonly string $addressLine,
        public readonly ?string $postalCode = null,   // ۱۰ رقم
        public readonly ?string $plate = null,
        public readonly ?string $unit = null,
        public readonly ?float $lat = null,
        public readonly ?float $lng = null,
        public readonly ?string $email = null,
        public readonly ?string $nationalId = null,   // ۱۰ رقم
    );

    public function fullName(): string;
    public function hasGeoCoordinates(): bool;
}
```

### Parcel

```php
final class Parcel
{
    public function __construct(
        public readonly int $weightGrams,
        public readonly ?int $lengthMm = null,
        public readonly ?int $widthMm = null,
        public readonly ?int $heightMm = null,
        public readonly ?Amount $declaredValue = null,
        public readonly ?string $contentsDescription = null,
        public readonly bool $fragile = false,
    );

    public function hasDimensions(): bool;
    public function volumetricWeightGrams(): ?int;
    public function chargeableWeightGrams(): int;
}
```

## Shipment

```php
final class Shipment implements ShipmentInterface { /* ر.ک. ShipmentInterface */ }

final class ShipmentId
{
    public function __construct(string $value);
    public function value(): string;
    public function equals(self $other): bool;
    public function __toString(): string;
}

enum ShipmentStatus: string
{
    case Draft = 'draft';
    case Quoted = 'quoted';
    case Booked = 'booked';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool;
    public function label(string $locale = 'fa'): string;
}
```

## Tracking

```php
final class TrackingEvent
{
    public function __construct(
        public readonly \DateTimeImmutable $at,
        public readonly ShipmentStatus $status,
        public readonly string $description,
        public readonly ?string $location = null,
        public readonly array $raw = [],
    );
}
```

## Requests

```php
final class QuoteRequest { /* origin, destination, parcel, serviceLevel, codAmount, extra */ }
final class Quote        { /* providerName, serviceLevel, cost, etaDays, quoteId, extra */ }
final class BookingRequest {
    public function withCashOnDelivery(Amount $amount): self;
    public function hasCashOnDelivery(): bool;
}
final class PickupRequest { /* windowStart, windowEnd, instructions, extra */ }
final class LabelResponse { /* format, bytes, url */ }
```

## Catalog

```php
final class Branch { /* id, name, city, address, phone, lat, lng, openingHours */ }

enum ServiceLevel: string
{
    case Standard = 'standard';
    case Express = 'express';
    case SameDay = 'same_day';
    case Economy = 'economy';

    public function label(string $locale = 'fa'): string;
}
```

## HTTP

```php
interface HttpClient
{
    public function request(string $method, string $url, ?string $body = null, array $headers = []): HttpResponse;
    public function postJson(string $url, string $body, array $headers = []): HttpResponse;
    public function getJson(string $url, array $headers = []): HttpResponse;
    public function deleteJson(string $url, array $headers = []): HttpResponse;
}

final class HttpResponse
{
    public int $statusCode;
    public string $body;
    public array $headers;
    public function header(string $name): ?string;
    public function isSuccessful(): bool;
}

interface Logger
{
    public function debug(string $message, array $context = []): void;
}

interface EventDispatcher
{
    public function dispatch(object $event): object;
}
```

## Events

| رویداد | فیلدها |
|--------|--------|
| `ShipmentQuoted` | `providerName`، `request`، `quotes` |
| `ShipmentCreated` | `providerName`، `shipment` |
| `ShipmentTracked` | `providerName`، `shipment` |
| `ShipmentCancelled` | `providerName`، `shipment` |
| `ShipmentFailed` | `providerName`، `operation`، `reason`، `errorCode` |

## Exceptions

| استثنا | ارث می‌برد از | متدهای اضافه |
|--------|----------------|---------------|
| `ErsalException` | `RuntimeException` | — |
| `InvalidAddressException` | `ErsalException` | — |
| `InvalidParcelException` | `ErsalException` | — |
| `InvalidAmountException` | `ErsalException` | — |
| `ConnectionException` | `ErsalException` | — |
| `ProviderException` | `ErsalException` | `getProviderName()`، `getErrorCode()` |
| `BookingException` | `ProviderException` | (ارث) |
| `TrackingException` | `ProviderException` | (ارث) |
| `CancellationException` | `ProviderException` | (ارث) |
