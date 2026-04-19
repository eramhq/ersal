# Ersal

A unified, type-safe PHP library for Iranian shipping and courier services.

[![Tests](https://github.com/eramhq/ersal/actions/workflows/tests.yml/badge.svg)](https://github.com/eramhq/ersal/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://php.net/)

**Documentation:** [English](docs/en/README.md) | [فارسی](docs/fa/README.md)

## What is Ersal?

Ersal (ارسال, "shipping" in Farsi) is an omni-carrier PHP library that gives you a single, consistent API across **major Iranian shipping providers** — from national postal services to private couriers and on-demand urban delivery. Write your shipping logic once, swap the carrier with one line of config.

**Why Ersal?**

- **Minimal dependencies** — one internal Composer package ([`eram/abzar`](https://github.com/eramhq/abzar-php), Persian/Iranian utilities) plus the standard PHP extensions `ext-curl`, `ext-json`, `ext-openssl`, `ext-soap`. No Guzzle, no framework coupling, no third-party supply-chain risk.
- **One interface, every carrier** — `quote()` → `createShipment()` → `track()` → `cancel()`. Same flow whether it's Iran Post or Alopeyk.
- **Type-safe value objects** — `Address`, `Parcel`, `ShipmentStatus`, `TrackingEvent` — no stringly-typed dictionaries.
- **Integer units everywhere** — grams for weight, millimeters for dimensions, Rials for money. Zero floating-point drift.
- **Framework-agnostic** — works with Laravel, Symfony, or plain PHP. Plug in your own HTTP client, logger, or event dispatcher.
- **Fully tested** — unit tests for every provider, PHPStan static analysis, PER-CS2 code style.

## Install

```bash
composer require eram/ersal
```

## Supported Carriers

| Alias | Carrier | Protocol | Capabilities | Docs |
|-------|---------|----------|--------------|------|
| `post` | Iran Post (شرکت پست) | SOAP + REST | Label, Branches | [Guide](docs/en/providers/post.md) |
| `tipax` | Tipax (تیپاکس) | REST | Label, Pickup, Branches, COD | [Guide](docs/en/providers/tipax.md) |
| `chapar` | Chapar Express | REST | Label, Pickup, COD | [Guide](docs/en/providers/chapar.md) |
| `mahex` | Mahex (ماهکس) | REST | Pickup, COD | [Guide](docs/en/providers/mahex.md) |
| `amadast` | Amadast (آمادست) | REST | — | [Guide](docs/en/providers/amadast.md) |
| `paygan` | Paygan (پایگان) | REST | COD | [Guide](docs/en/providers/paygan.md) |
| `alopeyk` | Alopeyk (الوپیک) | REST | Pickup | [Guide](docs/en/providers/alopeyk.md) |

## Quick Start

The flow is always: **quote → book → track → (optionally) cancel**.

See the [Getting Started](docs/en/getting-started.md) guide for a complete walkthrough with code examples.

## Documentation

Full documentation with API reference, cookbook, per-carrier guides, and more:

- [English Documentation](docs/en/README.md)
- [مستندات فارسی](docs/fa/README.md)

## About Eram

[Eram](https://github.com/eramhq) is a small engineering team building open-source developer tools for the Persian ecosystem. Our projects — [pardakht](https://github.com/eramhq/pardakht), [ersal](https://github.com/eramhq/ersal), [daynum](https://github.com/eramhq/daynum), [persian-kit](https://github.com/eramhq/persian-kit) — solve the everyday problems that Iranian developers run into: payment integration, shipping, calendar conversion, and string/number localization. Everything we ship is MIT-licensed, zero-dependency where possible, and built to be boring infrastructure you never have to think about.

## License

[MIT](LICENSE)
