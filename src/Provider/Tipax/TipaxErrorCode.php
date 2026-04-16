<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Tipax;

/**
 * Canonical Tipax API error codes.
 *
 * Values mirror HTTP-adjacent error codes Tipax returns in the
 * `error.code` field of error responses. Verify against the carrier's
 * current developer documentation — this list covers the common cases.
 */
enum TipaxErrorCode: int
{
    case Success = 0;
    case InvalidToken = 401;
    case Forbidden = 403;
    case NotFound = 404;
    case InvalidAddress = 1001;
    case UnservicedArea = 1002;
    case ParcelTooHeavy = 1003;
    case ParcelTooLarge = 1004;
    case InvalidCodAmount = 1005;
    case ShipmentAlreadyBooked = 1101;
    case ShipmentAlreadyCancelled = 1102;
    case ShipmentAlreadyPickedUp = 1103;
    case CancellationNotAllowed = 1104;
    case PickupWindowUnavailable = 1201;
    case RateLimitExceeded = 1901;
    case InternalError = 1999;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Success => 'عملیات موفق',
                self::InvalidToken => 'توکن نامعتبر',
                self::Forbidden => 'دسترسی غیرمجاز',
                self::NotFound => 'یافت نشد',
                self::InvalidAddress => 'آدرس نامعتبر',
                self::UnservicedArea => 'منطقه خارج از پوشش سرویس',
                self::ParcelTooHeavy => 'وزن بسته بیش از حد مجاز',
                self::ParcelTooLarge => 'ابعاد بسته بیش از حد مجاز',
                self::InvalidCodAmount => 'مبلغ پس‌کرایه نامعتبر',
                self::ShipmentAlreadyBooked => 'این مرسوله قبلاً ثبت شده است',
                self::ShipmentAlreadyCancelled => 'این مرسوله قبلاً لغو شده است',
                self::ShipmentAlreadyPickedUp => 'این مرسوله تحویل پیک داده شده است',
                self::CancellationNotAllowed => 'لغو این مرسوله مجاز نیست',
                self::PickupWindowUnavailable => 'بازه زمانی انتخاب‌شده برای جمع‌آوری در دسترس نیست',
                self::RateLimitExceeded => 'تعداد درخواست بیش از حد مجاز',
                self::InternalError => 'خطای داخلی',
            };
        }

        return match ($this) {
            self::Success => 'Operation successful',
            self::InvalidToken => 'Invalid token',
            self::Forbidden => 'Forbidden',
            self::NotFound => 'Not found',
            self::InvalidAddress => 'Invalid address',
            self::UnservicedArea => 'Area not serviced',
            self::ParcelTooHeavy => 'Parcel weight exceeds limit',
            self::ParcelTooLarge => 'Parcel dimensions exceed limit',
            self::InvalidCodAmount => 'Invalid cash-on-delivery amount',
            self::ShipmentAlreadyBooked => 'Shipment already booked',
            self::ShipmentAlreadyCancelled => 'Shipment already cancelled',
            self::ShipmentAlreadyPickedUp => 'Shipment already picked up',
            self::CancellationNotAllowed => 'Cancellation not allowed in current state',
            self::PickupWindowUnavailable => 'Selected pickup window unavailable',
            self::RateLimitExceeded => 'Rate limit exceeded',
            self::InternalError => 'Internal error',
        };
    }
}
