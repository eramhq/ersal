<?php

declare(strict_types=1);

namespace Eram\Ersal\Address;

use Eram\Ersal\Exception\InvalidAddressException;

/**
 * Immutable value object representing a pickup or delivery address.
 *
 * Iranian address conventions:
 * - Postal code: exactly 10 digits (کد پستی).
 * - Phone: normalized to Iranian E.164 (+989xxxxxxxxx). Local forms
 *   `09xxxxxxxxx` and `989xxxxxxxxx` are accepted and converted on construction.
 * - National ID (کد ملی): optional, some carriers require it for COD orders.
 */
final class Address
{
    public readonly string $phone;

    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        string $phone,
        public readonly string $province,
        public readonly string $city,
        public readonly string $addressLine,
        public readonly ?string $postalCode = null,
        public readonly ?string $plate = null,
        public readonly ?string $unit = null,
        public readonly ?float $lat = null,
        public readonly ?float $lng = null,
        public readonly ?string $email = null,
        public readonly ?string $nationalId = null,
    ) {
        if (trim($firstName) === '') {
            throw new InvalidAddressException('First name is required.');
        }

        if (trim($lastName) === '') {
            throw new InvalidAddressException('Last name is required.');
        }

        if (trim($province) === '') {
            throw new InvalidAddressException('Province is required.');
        }

        if (trim($city) === '') {
            throw new InvalidAddressException('City is required.');
        }

        if (trim($addressLine) === '') {
            throw new InvalidAddressException('Address line is required.');
        }

        $this->phone = self::normalizePhone($phone);

        if ($postalCode !== null && !preg_match('/^\d{10}$/', $postalCode)) {
            throw new InvalidAddressException("Postal code must be exactly 10 digits, got: {$postalCode}");
        }

        if ($nationalId !== null && !preg_match('/^\d{10}$/', $nationalId)) {
            throw new InvalidAddressException("National ID must be exactly 10 digits, got: {$nationalId}");
        }
    }

    public function fullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function hasGeoCoordinates(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    /**
     * Normalize Iranian mobile numbers to E.164 (+989xxxxxxxxx).
     *
     * Accepts: 09xxxxxxxxx, 989xxxxxxxxx, +989xxxxxxxxx, 00989xxxxxxxxx.
     */
    private static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (preg_match('/^00(98\d{10})$/', $digits, $m) === 1) {
            return '+' . $m[1];
        }

        if (preg_match('/^(98\d{10})$/', $digits, $m) === 1) {
            return '+' . $m[1];
        }

        if (preg_match('/^0(9\d{9})$/', $digits, $m) === 1) {
            return '+98' . $m[1];
        }

        if (preg_match('/^9\d{9}$/', $digits) === 1) {
            return '+98' . $digits;
        }

        throw new InvalidAddressException("Invalid Iranian phone number: {$phone}");
    }
}
