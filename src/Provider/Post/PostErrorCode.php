<?php

declare(strict_types=1);

namespace Eram\Ersal\Provider\Post;

/**
 * Iran Post SOAP service error codes.
 *
 * Codes mirror Iran Post's public SOAP fault catalog. Verify with the
 * currently-published WSDL documentation — Iran Post occasionally renumbers.
 */
enum PostErrorCode: int
{
    case Success = 0;
    case InvalidCredentials = 1001;
    case ContractNotFound = 1002;
    case ShipmentNotFound = 1010;
    case InvalidOriginPostalCode = 1020;
    case InvalidDestinationPostalCode = 1021;
    case UnservicedArea = 1030;
    case ParcelWeightExceeded = 1040;
    case ParcelDimensionsExceeded = 1041;
    case DuplicateTrackingCode = 1050;
    case CancellationNotAllowed = 1060;
    case ServiceUnavailable = 1999;
    case InternalError = 9999;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Success => 'عملیات موفق',
                self::InvalidCredentials => 'نام کاربری یا رمز عبور نامعتبر',
                self::ContractNotFound => 'قرارداد یافت نشد',
                self::ShipmentNotFound => 'مرسوله یافت نشد',
                self::InvalidOriginPostalCode => 'کد پستی مبدأ نامعتبر',
                self::InvalidDestinationPostalCode => 'کد پستی مقصد نامعتبر',
                self::UnservicedArea => 'منطقه خارج از پوشش سرویس',
                self::ParcelWeightExceeded => 'وزن بسته بیش از حد مجاز',
                self::ParcelDimensionsExceeded => 'ابعاد بسته بیش از حد مجاز',
                self::DuplicateTrackingCode => 'کد رهگیری تکراری',
                self::CancellationNotAllowed => 'لغو این مرسوله مجاز نیست',
                self::ServiceUnavailable => 'سرویس در دسترس نیست',
                self::InternalError => 'خطای داخلی',
            };
        }

        return match ($this) {
            self::Success => 'Operation successful',
            self::InvalidCredentials => 'Invalid credentials',
            self::ContractNotFound => 'Contract not found',
            self::ShipmentNotFound => 'Shipment not found',
            self::InvalidOriginPostalCode => 'Invalid origin postal code',
            self::InvalidDestinationPostalCode => 'Invalid destination postal code',
            self::UnservicedArea => 'Area not serviced',
            self::ParcelWeightExceeded => 'Parcel weight exceeds limit',
            self::ParcelDimensionsExceeded => 'Parcel dimensions exceed limit',
            self::DuplicateTrackingCode => 'Duplicate tracking code',
            self::CancellationNotAllowed => 'Cancellation not allowed in current state',
            self::ServiceUnavailable => 'Service unavailable',
            self::InternalError => 'Internal error',
        };
    }
}
