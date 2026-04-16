<?php

declare(strict_types=1);

namespace Eram\Ersal\Address;

use Eram\Ersal\Exception\InvalidParcelException;
use Eram\Ersal\Money\Amount;

/**
 * Immutable value object representing a physical parcel.
 *
 * Units are always integer grams (weight) and millimeters (dimensions)
 * to avoid floating-point drift. Each provider converts to its own
 * expected unit internally.
 */
final class Parcel
{
    public function __construct(
        public readonly int $weightGrams,
        public readonly ?int $lengthMm = null,
        public readonly ?int $widthMm = null,
        public readonly ?int $heightMm = null,
        public readonly ?Amount $declaredValue = null,
        public readonly ?string $contentsDescription = null,
        public readonly bool $fragile = false,
    ) {
        if ($weightGrams <= 0) {
            throw new InvalidParcelException("Parcel weight must be positive, got: {$weightGrams} grams");
        }

        foreach (['lengthMm' => $lengthMm, 'widthMm' => $widthMm, 'heightMm' => $heightMm] as $name => $value) {
            if ($value !== null && $value <= 0) {
                throw new InvalidParcelException("Parcel {$name} must be positive, got: {$value}");
            }
        }
    }

    public function hasDimensions(): bool
    {
        return $this->lengthMm !== null && $this->widthMm !== null && $this->heightMm !== null;
    }

    /**
     * Volumetric weight in grams using the standard 5000 divisor
     * (cm³ ÷ 5000 → kg, scaled to grams).
     */
    public function volumetricWeightGrams(): ?int
    {
        if (!$this->hasDimensions()) {
            return null;
        }

        /** @var int $l */
        $l = $this->lengthMm;
        /** @var int $w */
        $w = $this->widthMm;
        /** @var int $h */
        $h = $this->heightMm;

        $volumeCm3 = ($l * $w * $h) / 1000;

        return (int) ceil($volumeCm3 / 5);
    }

    /**
     * The greater of actual weight and volumetric weight, used for billing.
     */
    public function chargeableWeightGrams(): int
    {
        $volumetric = $this->volumetricWeightGrams();

        if ($volumetric === null) {
            return $this->weightGrams;
        }

        return max($this->weightGrams, $volumetric);
    }
}
