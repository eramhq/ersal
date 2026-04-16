<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Mahex;

enum MahexErrorCode: int
{
    case Success = 0;
    case InvalidToken = 401;
    case NotFound = 404;
    case InvalidPayload = 422;
    case UnservicedArea = 3001;
    case ParcelTooHeavy = 3002;
    case InvalidCodAmount = 3010;
    case CancellationNotAllowed = 3201;
    case RateLimitExceeded = 3901;
    case InternalError = 3999;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Success => 'عملیات موفق',
                self::InvalidToken => 'توکن نامعتبر',
                self::NotFound => 'یافت نشد',
                self::InvalidPayload => 'داده‌های ارسالی نامعتبر',
                self::UnservicedArea => 'منطقه خارج از پوشش',
                self::ParcelTooHeavy => 'وزن بسته بیش از حد مجاز',
                self::InvalidCodAmount => 'مبلغ پس‌کرایه نامعتبر',
                self::CancellationNotAllowed => 'لغو این مرسوله مجاز نیست',
                self::RateLimitExceeded => 'تعداد درخواست بیش از حد مجاز',
                self::InternalError => 'خطای داخلی',
            };
        }

        return match ($this) {
            self::Success => 'Operation successful',
            self::InvalidToken => 'Invalid token',
            self::NotFound => 'Not found',
            self::InvalidPayload => 'Invalid payload',
            self::UnservicedArea => 'Area not serviced',
            self::ParcelTooHeavy => 'Parcel weight exceeds limit',
            self::InvalidCodAmount => 'Invalid cash-on-delivery amount',
            self::CancellationNotAllowed => 'Cancellation not allowed in current state',
            self::RateLimitExceeded => 'Rate limit exceeded',
            self::InternalError => 'Internal error',
        };
    }
}
