<?php

declare(strict_types=1);

namespace Eram\Ersal\Catalog;

/**
 * Canonical service-level identifiers used across providers.
 *
 * Individual carriers map these to their own product codes. Carriers
 * may accept additional carrier-specific levels — use the raw string
 * form in that case and let the provider translate.
 */
enum ServiceLevel: string
{
    case Standard = 'standard';
    case Express = 'express';
    case SameDay = 'same_day';
    case Economy = 'economy';

    public function label(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Standard => 'استاندارد',
                self::Express => 'سریع',
                self::SameDay => 'همان‌روز',
                self::Economy => 'اقتصادی',
            };
        }

        return match ($this) {
            self::Standard => 'Standard',
            self::Express => 'Express',
            self::SameDay => 'Same day',
            self::Economy => 'Economy',
        };
    }
}
