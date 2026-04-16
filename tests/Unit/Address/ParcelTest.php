<?php

declare(strict_types=1);

namespace Eram\Ersal\Tests\Unit\Address;

use Eram\Ersal\Address\Parcel;
use Eram\Ersal\Exception\InvalidParcelException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParcelTest extends TestCase
{
    #[Test]
    public function weight_is_stored(): void
    {
        $parcel = new Parcel(weightGrams: 1500);

        $this->assertSame(1500, $parcel->weightGrams);
        $this->assertFalse($parcel->hasDimensions());
        $this->assertNull($parcel->volumetricWeightGrams());
        $this->assertSame(1500, $parcel->chargeableWeightGrams());
    }

    #[Test]
    public function volumetric_weight_is_computed(): void
    {
        // 30cm × 20cm × 10cm = 6000 cm³ → 6000 / 5 = 1200 g volumetric
        $parcel = new Parcel(
            weightGrams: 500,
            lengthMm: 300,
            widthMm: 200,
            heightMm: 100,
        );

        $this->assertTrue($parcel->hasDimensions());
        $this->assertSame(1200, $parcel->volumetricWeightGrams());
        $this->assertSame(1200, $parcel->chargeableWeightGrams(), 'should pick volumetric over actual');
    }

    #[Test]
    public function chargeable_picks_the_larger_of_actual_and_volumetric(): void
    {
        $parcel = new Parcel(
            weightGrams: 5000,
            lengthMm: 100,
            widthMm: 100,
            heightMm: 100,
        );

        // volumetric = 1,000,000 mm³ / 1000 = 1000 cm³ / 5 = 200 g
        $this->assertSame(200, $parcel->volumetricWeightGrams());
        $this->assertSame(5000, $parcel->chargeableWeightGrams());
    }

    #[Test]
    public function zero_weight_throws(): void
    {
        $this->expectException(InvalidParcelException::class);

        new Parcel(weightGrams: 0);
    }

    #[Test]
    public function negative_dimension_throws(): void
    {
        $this->expectException(InvalidParcelException::class);

        new Parcel(weightGrams: 100, lengthMm: -1);
    }
}
