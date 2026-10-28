<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Dates are stored in UTC; editors think in Romanian time. The admin's
 * datetime-local inputs send and receive wall-clock time in ZONE, converted
 * here in both directions (DST included).
 */
final class AdminTime
{
    public const string ZONE = 'Europe/Bucharest';

    public const string INPUT_FORMAT = 'Y-m-d\TH:i';

    public static function toInput(?CarbonInterface $moment): ?string
    {
        return $moment?->copy()->setTimezone(self::ZONE)->format(self::INPUT_FORMAT);
    }

    public static function fromInput(?string $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $local = CarbonImmutable::createFromFormat(self::INPUT_FORMAT, $value, self::ZONE);

        return $local === null ? null : $local->utc();
    }

    /**
     * A readable Romanian-time timestamp for lists: "30.09.2026 14:05".
     */
    public static function display(?CarbonInterface $moment): ?string
    {
        return $moment?->copy()->setTimezone(self::ZONE)->format('d.m.Y H:i');
    }
}
