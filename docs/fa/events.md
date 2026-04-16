# رویدادها

ارسال رویدادهای چرخه حیات را از طریق `EventDispatcher` اختیاری dispatch می‌کند. برای دریافت آن‌ها یک dispatcher به سازنده `Ersal` پاس دهید:

```php
$ersal = new Ersal(
    eventDispatcher: new MyDispatcher(),
);
```

## کاتالوگ رویدادها

همه رویدادها کلاس‌های data ساده با `public readonly` هستند.

| رویداد | فیلدها | چه زمانی dispatch می‌شود |
|--------|--------|---------------------------|
| `ShipmentQuoted` | `providerName`، `request`، `quotes` | `quote()` موفق برگشت |
| `ShipmentCreated` | `providerName`، `shipment` | `createShipment()` موفق شد |
| `ShipmentTracked` | `providerName`، `shipment` | `track()` موفق برگشت |
| `ShipmentCancelled` | `providerName`، `shipment` | `cancel()` موفق شد |
| `ShipmentFailed` | `providerName`، `operation`، `reason`، `errorCode` | هر عملیاتی شکست خورد |

`operation` در `ShipmentFailed` یکی از: `'quote'`، `'book'`، `'track'`، `'cancel'`، `'label'`، `'pickup'`.

## Dispatcher حداقلی

```php
use Eram\Ersal\Http\EventDispatcher;
use Eram\Ersal\Event\ShipmentCreated;
use Eram\Ersal\Event\ShipmentFailed;

final class AppDispatcher implements EventDispatcher
{
    public function dispatch(object $event): object
    {
        match (true) {
            $event instanceof ShipmentCreated => $this->onCreated($event),
            $event instanceof ShipmentFailed  => $this->onFailed($event),
            default => null,
        };

        return $event;
    }

    private function onCreated(ShipmentCreated $e): void
    {
        $this->logger->info('مرسوله ثبت شد', [
            'provider' => $e->providerName,
            'shipment_id' => $e->shipment->getId()->value(),
            'tracking_code' => $e->shipment->getTrackingCode(),
        ]);
    }

    private function onFailed(ShipmentFailed $e): void
    {
        $this->logger->error('عملیات مرسوله شکست خورد', [
            'provider' => $e->providerName,
            'operation' => $e->operation,
            'reason' => $e->reason,
            'code' => $e->errorCode,
        ]);
    }
}
```

## استفاده از Dispatcher استاندارد PSR-14

امضای `EventDispatcher::dispatch()` عمداً مشابه PSR-14 است (`object → object`). برای استفاده از dispatcher PSR-14 موجود، یک آداپتر نازک بنویسید:

```php
final class Psr14Adapter implements EventDispatcher
{
    public function __construct(private \Psr\EventDispatcher\EventDispatcherInterface $inner) {}

    public function dispatch(object $event): object
    {
        return $this->inner->dispatch($event);
    }
}
```

ارسال به PSR-14 متکی نیست — بدون وابستگی مستقیم `psr/event-dispatcher`، ولی قرارداد به صورت drop-in سازگار است.
