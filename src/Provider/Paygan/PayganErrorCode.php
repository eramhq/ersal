<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Paygan;

enum PayganErrorCode: int
{
    case Success = 0;
    case InvalidApiKey = 401;
    case NotFound = 404;
    case InvalidPayload = 422;
    case UnservicedArea = 4001;
    case InvalidCodAmount = 4010;
    case CancellationNotAllowed = 4201;
    case RateLimitExceeded = 4901;
    case InternalError = 4999;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Success => 'عملیات موفق',
                self::InvalidApiKey => 'کلید API نامعتبر',
                self::NotFound => 'یافت نشد',
                self::InvalidPayload => 'داده‌های نامعتبر',
                self::UnservicedArea => 'منطقه خارج از پوشش',
                self::InvalidCodAmount => 'مبلغ پس‌کرایه نامعتبر',
                self::CancellationNotAllowed => 'لغو مجاز نیست',
                self::RateLimitExceeded => 'تعداد درخواست بیش از حد مجاز',
                self::InternalError => 'خطای داخلی',
            };
        }

        return match ($this) {
            self::Success => 'Operation successful',
            self::InvalidApiKey => 'Invalid API key',
            self::NotFound => 'Not found',
            self::InvalidPayload => 'Invalid payload',
            self::UnservicedArea => 'Area not serviced',
            self::InvalidCodAmount => 'Invalid cash-on-delivery amount',
            self::CancellationNotAllowed => 'Cancellation not allowed',
            self::RateLimitExceeded => 'Rate limit exceeded',
            self::InternalError => 'Internal error',
        };
    }
}
