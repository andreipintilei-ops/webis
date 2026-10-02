<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Name, address and phone (NAP) as shown in the footer and in the
 * Organization / ProfessionalService JSON-LD. Keep it identical to the Google
 * Business Profile — local rankings compare the two.
 */
class CompanySettings extends Settings
{
    public string $name;

    public string $legal_name;

    /** Cod unic de înregistrare (CUI). */
    public ?string $vat_id;

    /** Număr de înregistrare la Registrul Comerțului. */
    public ?string $registration_number;

    public ?string $phone;

    public ?string $email;

    public ?string $street_address;

    public string $locality;

    public string $region;

    public ?string $postal_code;

    public string $country_code;

    public ?float $latitude;

    public ?float $longitude;

    /**
     * Opening hours, one row per range: [{days: ['Mo', ...], opens: '09:00', closes: '18:00'}].
     *
     * @phpstan-var list<array{days: list<string>, opens: string, closes: string}>
     */
    public array $opening_hours;

    /**
     * Profile URLs keyed by platform (facebook, instagram, linkedin...). They
     * become the Organization's `sameAs`.
     *
     * @phpstan-var array<string, string>
     */
    public array $social;

    public static function group(): string
    {
        return 'company';
    }
}
