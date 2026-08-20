<?php

namespace App\Actions;

final class NormalizePhoneAction
{
    private const string DEFAULT_COUNTRY_CODE = '55';

    /**
     * Normalize a phone number to the international digits-only format
     * expected by wa.me links, applying the default country code (55,
     * Brazil) when the number doesn't already carry one.
     *
     * Returns null when the phone is empty or cannot be normalized into
     * a valid number.
     */
    public function execute(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (! $this->hasCountryCode($digits)) {
            $digits = self::DEFAULT_COUNTRY_CODE.$digits;
        }

        return $this->isValid($digits) ? $digits : null;
    }

    /**
     * A Brazilian local number (with area code) is always 10 or 11 digits,
     * so once it's prefixed with the country code it becomes 12 or 13
     * digits. We treat a 12/13-digit number already starting with "55" as
     * already carrying the country code, rather than a coincidental area
     * code, since a bare local number can never reach that length.
     */
    private function hasCountryCode(string $digits): bool
    {
        return str_starts_with($digits, self::DEFAULT_COUNTRY_CODE)
            && $this->isValid($digits);
    }

    private function isValid(string $digits): bool
    {
        return in_array(strlen($digits), [12, 13], true);
    }
}
