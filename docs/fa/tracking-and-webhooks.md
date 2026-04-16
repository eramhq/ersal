# ردیابی و Webhookها

## Polling

ردیابی داخلی ارسال مبتنی بر polling است: هر زمان که بخواهید `$provider->track($id)` را فراخوانی کنید.

```php
$shipment = $provider->track(new ShipmentId('SHP-001'));

$shipment->getStatus();                // ShipmentStatus
$shipment->getStatus()->isTerminal();  // بعد از Delivered/Failed/Returned/Cancelled true می‌شود

foreach ($shipment->getHistory() as $event) {
    printf(
        "[%s] %s — %s\n",
        $event->at->format('Y-m-d H:i'),
        $event->status->label('fa'),
        $event->description,
    );
}
```

### الگوی حلقه polling

اکثر شرکت‌ها توصیه می‌کنند هر مرسوله بیش از یک بار در ۵ تا ۱۵ دقیقه polling نشود. یک poller حداقلی با cron:

```php
foreach ($openShipments as $row) {
    try {
        $shipment = $provider->track(new ShipmentId($row->shipment_id));
    } catch (TrackingException $e) {
        continue; // شرکت پاسخگو نیست — در tick بعد تلاش می‌کنیم
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

## Webhookها

ارسال v1 webhookها را parse نمی‌کند — شرکت‌ها شکل payload متفاوتی دارند، بعضی فقط به endpoint قراردادی push می‌کنند و API `WebhookParser` به طراحی دقیق برای هر شرکت نیاز دارد.

الگوی اصطلاحی: webhook را در کنترلر خود دریافت کنید، سپس با فراخوانی `$provider->track($id)` به ارسال پل بزنید تا `ShipmentInterface` نرمال‌شده بگیرید:

```php
// در handler webhook
$shipmentId = new ShipmentId($_POST['shipment_id']);
$shipment = $provider->track($shipmentId);  // وضعیت نرمال‌شده از ارسال

$queue->dispatch(new ShipmentUpdated($shipment));
```

مزیت اضافه: همیشه یک `ShipmentInterface` نرمال و type-safe تولید می‌کنید، فارغ از شکل webhook شرکت.

## payload خام

هر `TrackingEvent` یک فیلد `raw: array` با payload رویداد شرکت دارد. از این برای فیلدهایی که ارسال نرمال نمی‌کند استفاده کنید:

```php
foreach ($shipment->getHistory() as $event) {
    $courierName = $event->raw['courier_name'] ?? null;
}
```
