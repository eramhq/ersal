# Addresses & Parcels

## Address

`Address` is an immutable value object capturing a sender or recipient.

```php
$address = new Address(
    firstName: 'علی',
    lastName: 'رضایی',
    phone: '09123456789',            // normalized on construction
    province: 'تهران',
    city: 'تهران',
    addressLine: 'خیابان ولیعصر، پلاک 100',
    postalCode: '1234567890',        // 10 digits
    plate: '100',                    // optional پلاک
    unit: '3',                       // optional واحد
    lat: 35.6892, lng: 51.3890,      // optional geo
    email: 'ali@example.com',
    nationalId: '1234567890',        // optional کد ملی (some carriers need it for COD)
);

$address->phone;              // '+989123456789' — normalized
$address->fullName();         // 'علی رضایی'
$address->hasGeoCoordinates(); // true
```

### Phone normalization

The constructor accepts several Iranian mobile formats and normalizes to E.164 (`+989xxxxxxxxx`):

| Input | Stored as |
|-------|-----------|
| `09123456789` | `+989123456789` |
| `989123456789` | `+989123456789` |
| `+989123456789` | `+989123456789` |
| `00989123456789` | `+989123456789` |
| `0912 345 6789` | `+989123456789` |
| `0912-345-6789` | `+989123456789` |

Anything else throws `InvalidAddressException`.

### Postal code

Iranian postal codes are exactly 10 digits. Any other value throws `InvalidAddressException`.

### National ID

Optional. When provided, must be 10 digits. Required by some carriers for cash-on-delivery shipments.

## Parcel

`Parcel` uses integer grams and millimeters to avoid floating-point drift.

```php
$parcel = new Parcel(
    weightGrams: 1500,         // 1.5 kg
    lengthMm: 300,             // 30 cm
    widthMm: 200,              // 20 cm
    heightMm: 100,             // 10 cm
    declaredValue: Amount::fromToman(250_000),  // optional insurance value
    contentsDescription: 'کتاب',
    fragile: false,
);
```

Each provider converts to its own expected unit (most use kilograms and centimeters; some SOAP APIs want grams and millimeters directly).

### Volumetric weight

Carriers bill by the greater of actual weight and volumetric weight. The standard formula is:

```
volumetric_weight_kg = (length_cm × width_cm × height_cm) / 5000
```

Ersal computes this for you:

```php
$parcel->volumetricWeightGrams();   // int|null (null if no dimensions)
$parcel->chargeableWeightGrams();   // max(actual, volumetric)
```

### Validation

- `weightGrams` must be > 0
- Each dimension, if provided, must be > 0

Any violation throws `InvalidParcelException`.
