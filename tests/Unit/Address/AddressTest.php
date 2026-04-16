<?php

declare(strict_types=1);

namespace Eram\Ersal\Tests\Unit\Address;

use Eram\Ersal\Address\Address;
use Eram\Ersal\Exception\InvalidAddressException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    #[Test]
    #[DataProvider('phoneVariations')]
    public function phone_is_normalized(string $input, string $expected): void
    {
        $address = $this->makeAddress(phone: $input);

        $this->assertSame($expected, $address->phone);
    }

    public static function phoneVariations(): array
    {
        return [
            'local 09xx'    => ['09123456789', '+989123456789'],
            'international' => ['+989123456789', '+989123456789'],
            'without plus'  => ['989123456789', '+989123456789'],
            'with 00'       => ['00989123456789', '+989123456789'],
            'with spaces'   => ['0912 345 6789', '+989123456789'],
            'with dashes'   => ['0912-345-6789', '+989123456789'],
        ];
    }

    #[Test]
    public function invalid_phone_throws(): void
    {
        $this->expectException(InvalidAddressException::class);

        $this->makeAddress(phone: '123');
    }

    #[Test]
    public function invalid_postal_code_throws(): void
    {
        $this->expectException(InvalidAddressException::class);

        $this->makeAddress(postalCode: '12345');
    }

    #[Test]
    public function valid_postal_code_is_accepted(): void
    {
        $address = $this->makeAddress(postalCode: '1234567890');

        $this->assertSame('1234567890', $address->postalCode);
    }

    #[Test]
    public function full_name_concatenates(): void
    {
        $address = $this->makeAddress();

        $this->assertSame('Ali Rezaei', $address->fullName());
    }

    #[Test]
    public function empty_required_field_throws(): void
    {
        $this->expectException(InvalidAddressException::class);

        $this->makeAddress(firstName: '   ');
    }

    private function makeAddress(
        string $firstName = 'Ali',
        string $lastName = 'Rezaei',
        string $phone = '09123456789',
        string $province = 'تهران',
        string $city = 'تهران',
        string $addressLine = 'خیابان ولیعصر، پلاک 1',
        ?string $postalCode = null,
    ): Address {
        return new Address(
            firstName: $firstName,
            lastName: $lastName,
            phone: $phone,
            province: $province,
            city: $city,
            addressLine: $addressLine,
            postalCode: $postalCode,
        );
    }
}
