<?php

namespace App\Support\Connect;

class PhoneNumber
{
    /**
     * Normalise any phone string to E.164 (e.g. +17325551111).
     *
     * Rules:
     *  - Already starts with "+"  -> keep digits after it.
     *  - 10 digits                -> prepend default country code.
     *  - 11 digits starting "1"   -> prepend "+".
     *  - Anything else            -> best-effort "+<digits>".
     */
    public static function toE164(?string $number): ?string
    {
        if ($number === null || trim($number) === '') {
            return null;
        }

        $cc = (string) config('connect.default_country_code', '1');

        // Preserve a leading + if present.
        $hasPlus = str_starts_with(trim($number), '+');
        $digits  = preg_replace('/\D+/', '', $number) ?? '';

        if ($digits === '') {
            return null;
        }

        if ($hasPlus) {
            return '+' . $digits;
        }

        // North-America style helpers (default cc = 1).
        if ($cc === '1') {
            if (strlen($digits) === 10) {
                return '+1' . $digits;
            }
            if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
                return '+' . $digits;
            }
        }

        // Generic fallback: assume the number already carries a country code.
        return '+' . $digits;
    }

    /** Loose equality check between two phone numbers after normalisation. */
    public static function matches(?string $a, ?string $b): bool
    {
        $a = self::toE164($a);
        $b = self::toE164($b);

        return $a !== null && $a === $b;
    }
}
