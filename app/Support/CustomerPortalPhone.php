<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class CustomerPortalPhone
{
    private const DIAL_CODES = [
        'BJ' => '229', 'BF' => '226', 'CI' => '225', 'GN' => '224', 'ML' => '223',
        'MR' => '222', 'NE' => '227', 'SN' => '221', 'TG' => '228', 'CG' => '242', 'CD' => '243',
    ];

    public static function dialCodes(): array
    {
        return self::DIAL_CODES;
    }

    /** @return array<string, array{label: string, cities: list<string>}> */
    public static function countries(): array
    {
        return array_intersect_key(WestAfricaShippingCatalog::countries(), self::DIAL_CODES);
    }

    public static function toE164(string $countryCode, string $phone): string
    {
        $dialCode = self::DIAL_CODES[strtoupper($countryCode)] ?? null;
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (! $dialCode || $digits === '') {
            throw ValidationException::withMessages(['phone' => 'Vérifie le pays et le numéro de téléphone.']);
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, $dialCode) && strlen($digits) > strlen($dialCode) + 5) {
            $digits = substr($digits, strlen($dialCode));
        }

        if (strlen($digits) < 6 || strlen($digits) > 14) {
            throw ValidationException::withMessages(['phone' => 'Saisis un numéro de téléphone valide.']);
        }

        return '+'.$dialCode.$digits;
    }
}
