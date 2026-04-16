# Events

Ersal dispatches lifecycle events through the optional `EventDispatcher`. Pass a dispatcher to the `Ersal` constructor to receive them:

```php
$ersal = new Ersal(
    eventDispatcher: new MyDispatcher(),
);
```

## Event catalog

All events are simple data classes with `public readonly` fields.

| Event | Fields | Dispatched when |
|-------|--------|-----------------|
| `ShipmentQuoted` | `providerName`, `request`, `quotes` | `quote()` returns successfully |
| `ShipmentCreated` | `providerName`, `shipment` | `createShipment()` succeeds |
| `ShipmentTracked` | `providerName`, `shipment` | `track()` returns successfully |
| `ShipmentCancelled` | `providerName`, `shipment` | `cancel()` succeeds |
| `ShipmentFailed` | `providerName`, `operation`, `reason`, `errorCode` | Any operation fails |

`operation` on `ShipmentFailed` is one of: `'quote'`, `'book'`, `'track'`, `'cancel'`, `'label'`, `'pickup'`.

## Minimal dispatcher

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
        $this->logger->info('Shipment booked', [
            'provider' => $e->providerName,
            'shipment_id' => $e->shipment->getId()->value(),
            'tracking_code' => $e->shipment->getTrackingCode(),
        ]);
    }

    private function onFailed(ShipmentFailed $e): void
    {
        $this->logger->error('Shipment operation failed', [
            'provider' => $e->providerName,
            'operation' => $e->operation,
            'reason' => $e->reason,
            'code' => $e->errorCode,
        ]);
    }
}
```

## Using a PSR-14 dispatcher

`EventDispatcher::dispatch()` deliberately mirrors PSR-14's signature (`object → object`). To use an existing PSR-14 dispatcher, write a thin adapter:

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

Ersal stays PSR-14-neutral — no direct `psr/event-dispatcher` dependency, but the contract is a drop-in match.
