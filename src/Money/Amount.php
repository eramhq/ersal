<?php

declare(strict_types=1);

namespace Eram\Ersal\Money;

use Eram\Ersal\Exception\InvalidAmountException;

/**
 * Immutable value object representing a monetary amount.
 *
 * Internally always stores the amount in Rials (IRR) to avoid
 * the Rial/Toman conversion bugs that plague Iranian e-commerce code.
 */
final class Amount
{
    private function __construct(
        private int $rials,
    ) {
        if ($rials < 0) {
            throw new InvalidAmountException('Amount cannot be negative.');
        }
    }

    public static function fromRials(int $rials): self
    {
        return new self($rials);
    }

    public static function fromToman(int $toman): self
    {
        return new self($toman * 10);
    }

    public function inRials(): int
    {
        return $this->rials;
    }

    public function inToman(): int
    {
        return (int) ($this->rials / 10);
    }

    public function equals(self $other): bool
    {
        return $this->rials === $other->rials;
    }

    public function isZero(): bool
    {
        return $this->rials === 0;
    }

    public function add(self $other): self
    {
        return new self($this->rials + $other->rials);
    }

    public function subtract(self $other): self
    {
        return new self($this->rials - $other->rials);
    }

    public function greaterThan(self $other): bool
    {
        return $this->rials > $other->rials;
    }

    public function lessThan(self $other): bool
    {
        return $this->rials < $other->rials;
    }

    public function __toString(): string
    {
        return (string) $this->rials;
    }
}
