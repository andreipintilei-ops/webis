<?php

namespace App\Support;

/**
 * A phone number as a tel: link, in the international form a phone dials
 * from anywhere: "0770 700 607" → "tel:+40770700607". The number shown on
 * the page stays as written.
 */
final class PhoneLink
{
    public static function href(?string $phone): ?string
    {
        $digits = preg_replace('/[^\d+]/', '', (string) $phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        $international = match (true) {
            str_starts_with($digits, '+') => $digits,
            str_starts_with($digits, '00') => '+'.substr($digits, 2),
            // A Romanian national number: its leading 0 is the trunk prefix.
            str_starts_with($digits, '0') => '+40'.substr($digits, 1),
            default => $digits,
        };

        return 'tel:'.$international;
    }
}
