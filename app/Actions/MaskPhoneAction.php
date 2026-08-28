<?php

namespace App\Actions;

final class MaskPhoneAction
{
    private const int VISIBLE_SUFFIX_LENGTH = 4;

    /**
     * Mask a phone number for logging under the PII policy, keeping only
     * the leading country/area digits and a short trailing suffix visible
     * (e.g. "+55***1234"). Returns null when the phone is empty.
     */
    public function execute(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) <= self::VISIBLE_SUFFIX_LENGTH) {
            return '+'.str_repeat('*', strlen($digits));
        }

        $suffix = substr($digits, -self::VISIBLE_SUFFIX_LENGTH);
        $prefixLength = strlen($digits) - self::VISIBLE_SUFFIX_LENGTH;
        $countryCodeLength = min(2, $prefixLength);
        $countryCode = substr($digits, 0, $countryCodeLength);

        return '+'.$countryCode.'***'.$suffix;
    }
}
