<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Alopeyk;

enum AlopeykErrorCode: int
{
    case Success = 0;
    case InvalidToken = 401;
    case NotFound = 404;
    case InvalidPayload = 422;
    case NoCourierAvailable = 5001;
    case UnservicedArea = 5002;
    case InvalidWindow = 5003;
    case CancellationNotAllowed = 5201;
    case RateLimitExceeded = 5901;
    case InternalError = 5999;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Success => 'عملیات موفق',
                self::InvalidToken => 'توکن نامعتبر',
                self::NotFound => 'یافت نشد',
                self::InvalidPayload => 'داده‌های نامعتبر',
                self::NoCourierAvailable => 'پیک در دسترس نیست',
                self::UnservicedArea => 'منطقه خارج از پوشش',
                self::InvalidWindow => 'بازه زمانی نامعتبر',
                self::CancellationNotAllowed => 'لغو مجاز نیست',
                self::RateLimitExceeded => 'تعداد درخواست بیش از حد مجاز',
                self::InternalError => 'خطای داخلی',
            };
        }

        return match ($this) {
            self::Success => 'Operation successful',
            self::InvalidToken => 'Invalid token',
            self::NotFound => 'Not found',
            self::InvalidPayload => 'Invalid payload',
            self::NoCourierAvailable => 'No courier available',
            self::UnservicedArea => 'Area not serviced',
            self::InvalidWindow => 'Invalid time window',
            self::CancellationNotAllowed => 'Cancellation not allowed',
            self::RateLimitExceeded => 'Rate limit exceeded',
            self::InternalError => 'Internal error',
        };
    }
}
