<?php

declare(strict_types=1);

namespace Eram\Ersal\Shipment;

enum ShipmentStatus: string
{
    case Draft = 'draft';
    case Quoted = 'quoted';
    case Booked = 'booked';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Delivered, self::Failed, self::Returned, self::Cancelled => true,
            default => false,
        };
    }

    /**
     * Map a carrier-reported status string to a canonical ShipmentStatus.
     *
     * Accepts the canonical value (`'in_transit'`) plus common synonyms
     * across Iranian carriers (`'pending'`, `'rated'`, `'on_the_way'`,
     * `'delivering'`, `'registered'`, `'completed'`, etc.). Unknown strings
     * fall back to `InTransit` — a safe mid-flight default.
     */
    public static function fromCanonical(string $raw): self
    {
        return match (strtolower($raw)) {
            'draft' => self::Draft,
            'quoted', 'rated' => self::Quoted,
            'booked', 'accepted', 'pending', 'registered' => self::Booked,
            'picked_up', 'collected' => self::PickedUp,
            'in_transit', 'shipping', 'dispatched', 'on_the_way' => self::InTransit,
            'out_for_delivery', 'delivering', 'arriving' => self::OutForDelivery,
            'delivered', 'completed' => self::Delivered,
            'failed', 'undeliverable' => self::Failed,
            'returned' => self::Returned,
            'cancelled', 'canceled' => self::Cancelled,
            default => self::InTransit,
        };
    }

    public function label(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Draft => 'پیش‌نویس',
                self::Quoted => 'قیمت‌گذاری شده',
                self::Booked => 'ثبت شده',
                self::PickedUp => 'تحویل به پیک',
                self::InTransit => 'در حال ارسال',
                self::OutForDelivery => 'در حال تحویل',
                self::Delivered => 'تحویل داده شد',
                self::Failed => 'ناموفق',
                self::Returned => 'مرجوع',
                self::Cancelled => 'لغو شده',
            };
        }

        return match ($this) {
            self::Draft => 'Draft',
            self::Quoted => 'Quoted',
            self::Booked => 'Booked',
            self::PickedUp => 'Picked up',
            self::InTransit => 'In transit',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
            self::Returned => 'Returned',
            self::Cancelled => 'Cancelled',
        };
    }
}
