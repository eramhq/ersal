# Money

Iranian commerce mixes Rial and Toman interchangeably. 1 Toman = 10 Rials. Carrier APIs typically expect Rials, but merchants think in Toman. A 10x error in either direction is catastrophic.

Ersal solves this with the `Amount` value object:

```php
use Eram\Ersal\Money\Amount;

$cost = Amount::fromToman(50_000);  // merchant thinks in Toman
$cost->inRials();                    // 500,000 — carrier API gets Rials
$cost->inToman();                    // 50,000 — display gets Toman
```

`Amount` stores everything internally in Rials. Each provider knows which unit its API expects and converts automatically.

## Construction

```php
Amount::fromRials(500_000);  // 500,000 Rials = 50,000 Toman
Amount::fromToman(50_000);   // 50,000 Toman = 500,000 Rials
```

Negative amounts throw `InvalidAmountException`.

## Arithmetic (immutable)

```php
$a = Amount::fromToman(10_000);
$b = Amount::fromToman(3_000);

$c = $a->add($b);        // 13,000 Toman (new Amount)
$d = $a->subtract($b);   // 7,000 Toman (new Amount)
// $a is unchanged
```

## Comparison

```php
$a->equals($b);        // bool
$a->greaterThan($b);   // bool
$a->lessThan($b);      // bool
$a->isZero();          // bool
```

