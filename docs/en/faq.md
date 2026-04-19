# FAQ

## Why so few Composer dependencies?

Iranian developers often work in restricted-network environments (sanctions, corporate proxies, intermittent Packagist access). A minimal dependency footprint means:

- Less to audit for supply-chain risk
- Install works offline once the source is vendored
- No framework-specific coupling — the same code runs on Laravel, Symfony, Slim, or plain PHP

Ersal's only Composer dependency is [`eram/abzar`](https://github.com/eramhq/abzar-php) — our own Persian/Iranian utility library, MIT-licensed and itself free of third-party runtime deps. Everything else comes from PHP itself: `ext-curl`, `ext-json`, `ext-openssl`, `ext-soap`.

## Why integer grams and millimeters?

Float arithmetic loses precision silently (`0.1 + 0.2 !== 0.3`). Shipment weights and dimensions cross carrier APIs where a rounding error can change billing tier. Integers make the math obvious and reproducible.

## Why no webhook parsing in v1?

Every carrier uses a different webhook shape. A clean abstraction needs a per-carrier `WebhookParser` class, and getting that right requires real traffic samples from each carrier. Until then, the documented bridge pattern (receive the webhook → call `track()`) produces a normalized `ShipmentInterface` without Ersal having to guess at payload shapes.

## Can I add my own carrier?

Yes — follow [CONTRIBUTING.md](../../CONTRIBUTING.md). Three files (Config / Provider / ErrorCode) plus a test plus registration in `Ersal::create()`.

## Why is `SupportsCOD` a marker interface with no methods?

Cash-on-delivery is an attribute of a booking, not a separate verb. `SupportsCOD` tells you "this carrier will honor `BookingRequest::$codAmount`" without adding an extra method. You set `$codAmount` directly (or via `BookingRequest::withCashOnDelivery()`), and the provider serializes it if supported.

## Why doesn't `track()` take a tracking code?

A tracking code is carrier-facing and human-readable. A `ShipmentId` is the opaque handle your code owns. These are distinct even when they happen to have the same string value — always use `ShipmentId` internally.

## What PHP versions are supported?

PHP 8.1, 8.2, 8.3, 8.4. CI runs all four.

## Is it safe to use in production?

The library itself is unit-tested end-to-end and static-analyzed at PHPStan level 6. However, carrier endpoint URLs and field names in the v1 release are based on public documentation conventions — verify against your carrier's current developer portal before putting into a production billing path. Each provider's `Config` accepts a custom `baseUrl` / `wsdlUrl` override for exactly this reason.

## How do I mock Ersal in tests?

Inject a mocked `HttpClient` via the `Ersal` constructor. Every provider ultimately routes through `HttpClient::postJson` / `getJson` / `deleteJson` — you can make them return canned `HttpResponse` bodies.

See `tests/Unit/Provider/TipaxProviderTest.php` for a complete example.
