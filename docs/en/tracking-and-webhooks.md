# Tracking & Webhooks

## Polling

Ersal's built-in tracking is polling-based: call `$provider->track($id)` whenever you want a fresh snapshot.

```php
$shipment = $provider->track(new ShipmentId('SHP-001'));

$shipment->getStatus();          // ShipmentStatus
$shipment->getStatus()->isTerminal();  // true once Delivered/Failed/Returned/Cancelled

foreach ($shipment->getHistory() as $event) {
    printf(
        "[%s] %s — %s\n",
        $event->at->format('Y-m-d H:i'),
        $event->status->label('en'),
        $event->description,
    );
}
```

### Polling loop pattern

Most carriers recommend polling no more than once per 5–15 minutes per shipment. A minimal cron-backed poller:

```php
foreach ($openShipments as $row) {
    try {
        $shipment = $provider->track(new ShipmentId($row->shipment_id));
    } catch (TrackingException $e) {
        continue; // carrier down or rate-limited — try next tick
    }

    $db->updateShipment($row->id, [
        'status' => $shipment->getStatus()->value,
        'updated_at' => now(),
    ]);

    if ($shipment->getStatus()->isTerminal()) {
        $db->markClosed($row->id);
    }
}
```

## Webhooks

Ersal v1 does not parse webhooks — carriers use different payload shapes, some push only to contracted endpoints, and WebhookParser APIs deserve careful per-carrier design.

The idiomatic pattern: receive the webhook in your own controller, then bridge into Ersal by calling `$provider->track($id)` to get the normalized `ShipmentInterface`:

```php
// In your webhook handler
$shipmentId = new ShipmentId($_POST['shipment_id']);
$shipment = $provider->track($shipmentId);  // normalized state from Ersal

$queue->dispatch(new ShipmentUpdated($shipment));
```

This has the bonus of always producing a normalized, type-safe `ShipmentInterface` regardless of the carrier-specific webhook shape.

## The `raw` payload

Each `TrackingEvent` carries a `raw: array` field with the carrier-native event payload. Use this if you need fields Ersal doesn't normalize:

```php
foreach ($shipment->getHistory() as $event) {
    $courierName = $event->raw['courier_name'] ?? null;
}
```
