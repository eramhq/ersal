# Error Handling

## Exception hierarchy

```
RuntimeException
└── ErsalException
    ├── ConnectionException        # transport / TLS / JSON decode failure
    ├── InvalidAddressException    # bad phone / postal code / missing field
    ├── InvalidParcelException     # non-positive weight or dimension
    ├── InvalidAmountException     # negative Rial value
    └── ProviderException          # carrier returned an error
        ├── BookingException       # createShipment failed
        ├── TrackingException      # track() target not found
        └── CancellationException  # cancel() rejected by carrier state
```

All Ersal exceptions extend `ErsalException`. A single top-level `catch (ErsalException $e)` covers everything.

## ProviderException

Thrown by every provider operation when the carrier returns an error. Exposes:

```php
$e->getProviderName();   // 'tipax', 'post', ...
$e->getErrorCode();      // int|string, carrier-specific
$e->getMessage();        // localized message (Persian by default)
```

The specific subclass tells you which operation failed:

```php
try {
    $provider->createShipment($request);
} catch (BookingException $e) {
    // booking specifically
} catch (ConnectionException $e) {
    // network/transport — safe to retry
} catch (ProviderException $e) {
    // other carrier errors
}
```

## Retry strategy

- **`ConnectionException`** — transient transport failure. Retry with backoff.
- **`TrackingException` with code = 404** — shipment not found; don't retry.
- **`BookingException` with rate-limit code** — back off and retry later.
- **Other `ProviderException`** — generally permanent; log and surface to operator.

## Error codes per provider

Each provider ships its own error code enum with bilingual messages:

```php
use Eram\Ersal\Provider\Tipax\TipaxErrorCode;

try {
    $provider->createShipment($request);
} catch (BookingException $e) {
    $code = TipaxErrorCode::tryFrom($e->getErrorCode());
    echo $code?->message('en') ?? $e->getMessage();
}
```

Provider-specific enums:

- `Eram\Ersal\Provider\Post\PostErrorCode`
- `Eram\Ersal\Provider\Tipax\TipaxErrorCode`
- `Eram\Ersal\Provider\Chapar\ChaparErrorCode`
- `Eram\Ersal\Provider\Mahex\MahexErrorCode`
- `Eram\Ersal\Provider\Amadast\AmadastErrorCode`
- `Eram\Ersal\Provider\Paygan\PayganErrorCode`
- `Eram\Ersal\Provider\Alopeyk\AlopeykErrorCode`

Every enum has `message(string $locale = 'fa')` — pass `'en'` for English.
