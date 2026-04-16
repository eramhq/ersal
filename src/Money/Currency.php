<?php

declare(strict_types=1);

namespace Eram\Ersal\Money;

enum Currency: string
{
    /**
     * Iranian Rial — the official currency unit used by carrier APIs.
     */
    case IRR = 'IRR';

    /**
     * Iranian Toman — the common display unit (1 Toman = 10 Rials).
     */
    case IRT = 'IRT';

    public function label(): string
    {
        return match ($this) {
            self::IRR => 'ریال',
            self::IRT => 'تومان',
        };
    }
}
