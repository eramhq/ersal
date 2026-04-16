<?php

declare(strict_types=1);

namespace Eram\Ersal\Tests\Unit\Money;

use Eram\Ersal\Exception\InvalidAmountException;
use Eram\Ersal\Money\Amount;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AmountTest extends TestCase
{
    #[Test]
    public function from_rials_stores_rials(): void
    {
        $amount = Amount::fromRials(500_000);

        $this->assertSame(500_000, $amount->inRials());
        $this->assertSame(50_000, $amount->inToman());
    }

    #[Test]
    public function from_toman_converts_to_rials(): void
    {
        $amount = Amount::fromToman(50_000);

        $this->assertSame(500_000, $amount->inRials());
        $this->assertSame(50_000, $amount->inToman());
    }

    #[Test]
    public function negative_amount_throws(): void
    {
        $this->expectException(InvalidAmountException::class);

        Amount::fromRials(-1);
    }

    #[Test]
    public function add_and_subtract_return_new_instance(): void
    {
        $a = Amount::fromRials(1000);
        $b = Amount::fromRials(500);

        $this->assertSame(1500, $a->add($b)->inRials());
        $this->assertSame(500, $a->subtract($b)->inRials());
        $this->assertSame(1000, $a->inRials(), 'original must not mutate');
    }

    #[Test]
    public function comparison_methods(): void
    {
        $a = Amount::fromRials(1000);
        $b = Amount::fromRials(500);
        $c = Amount::fromRials(1000);

        $this->assertTrue($a->greaterThan($b));
        $this->assertTrue($b->lessThan($a));
        $this->assertTrue($a->equals($c));
        $this->assertFalse($a->isZero());
        $this->assertTrue(Amount::fromRials(0)->isZero());
    }

    #[Test]
    public function to_string_returns_rials(): void
    {
        $this->assertSame('500000', (string) Amount::fromToman(50_000));
    }
}
