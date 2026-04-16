<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Chapar;

enum ChaparErrorCode: int
{
    case Success = 0;
    case InvalidApiKey = 401;
    case NotFound = 404;
    case InvalidPayload = 422;
    case UnservicedArea = 2001;
    case ParcelTooHeavy = 2002;
    case ParcelTooLarge = 2003;
    case InvalidCodAmount = 2010;
    case DuplicateOrderId = 2101;
    case CancellationNotAllowed = 2201;
    case RateLimitExceeded = 2901;
    case InternalError = 2999;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Success => 'عملیات موفق',
                self::InvalidApiKey => 'کلید API نامعتبر',
                self::NotFound => 'یافت نشد',
                self::InvalidPayload => 'داده‌های ارسالی نامعتبر',
                self::UnservicedArea => 'منطقه خارج از پوشش',
                self::ParcelTooHeavy => 'وزن بسته بیش از حد مجاز',
                self::ParcelTooLarge => 'ابعاد بسته بیش از حد مجاز',
                self::InvalidCodAmount => 'مبلغ پس‌کرایه نامعتبر',
                self::DuplicateOrderId => 'شناسه سفارش تکراری',
                self::CancellationNotAllowed => 'لغو این مرسوله مجاز نیست',
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
            self::ParcelTooHeavy => 'Parcel weight exceeds limit',
            self::ParcelTooLarge => 'Parcel dimensions exceed limit',
            self::InvalidCodAmount => 'Invalid cash-on-delivery amount',
            self::DuplicateOrderId => 'Duplicate order ID',
            self::CancellationNotAllowed => 'Cancellation not allowed in current state',
            self::RateLimitExceeded => 'Rate limit exceeded',
            self::InternalError => 'Internal error',
        };
    }
}
