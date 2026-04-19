# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

## [0.2.0-beta.1] - 2026-04-19

### Changed
- Now depends on [`eram/abzar`](https://github.com/eramhq/abzar-php) `^0.6@beta` for shared Iranian utilities.
- `Eram\Ersal\Money\Amount` → **moved** to `Eram\Abzar\Money\Amount`. Update imports.

### Removed
- `Eram\Ersal\Money\Amount` — use `Eram\Abzar\Money\Amount`.
- `Eram\Ersal\Exception\InvalidAmountException` — negative amounts now throw `Eram\Abzar\Exception\FormatException` with `ErrorCode::AMOUNT_NEGATIVE`. Note that this exception does **not** extend `ErsalException`, so host apps catching `ErsalException` must also catch `Eram\Abzar\Exception\AbzarException`.

### Docs
- Reworded "zero dependencies" / "zero Composer dependencies" claims across `README.md`, `docs/en/README.md`, `docs/en/getting-started.md`, `docs/en/faq.md`, `docs/en/http-and-logging.md`, and the composer `description` to reflect the single `eram/abzar` runtime dependency.

## [0.1.0-beta.1] - 2026-04-16

### Added
- Core contracts: `ShippingInterface`, `ShipmentInterface`, `SupportsLabel`, `SupportsPickup`, `SupportsBranches`, `SupportsCOD`
- Money value objects: `Amount` (Rial/Toman safe), `Currency` enum
- Address and parcel value objects with Iranian phone/postal-code validation, integer grams/mm units, volumetric weight calculation
- Shipment DTOs: `Shipment`, `ShipmentId`, `ShipmentStatus` (10-state enum with terminal detection)
- Request DTOs: `QuoteRequest`, `Quote`, `BookingRequest`, `PickupRequest`, `LabelResponse`
- Tracking: `TrackingEvent` with carrier-native raw payload preservation
- Catalog: `Branch`, `ServiceLevel`
- HTTP layer: `HttpClient` with `request`/`postJson`/`getJson`/`deleteJson`, `CurlHttpClient` with persistent handle, `HttpResponse`, `SoapClientFactory`, `Logger`, `NullLogger`, `EventDispatcher`
- SOAP provider: Iran Post (post) with label + branches
- REST providers: Tipax, Chapar, Mahex, Amadast, Paygan, Alopeyk with provider-specific capability matrix
- Lifecycle events: `ShipmentQuoted`, `ShipmentCreated`, `ShipmentTracked`, `ShipmentCancelled`, `ShipmentFailed`
- Exception hierarchy with provider-specific bilingual error code enums (Persian + English)
- `Ersal` factory class with alias-based provider creation
- Full English + Persian documentation with per-provider guides
- Unit tests for core value objects and every provider, PHPStan level 6 clean, PER-CS2.0 compliant

[0.1.0-beta.1]: https://github.com/eramhq/ersal/releases/tag/v0.1.0-beta.1
