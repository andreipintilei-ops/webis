<?php

namespace App\Enums;

/**
 * The kind of organisation a client is — how /clienti groups them, and the
 * anchor of each group there (/clienti#universitati…).
 */
enum ClientType: string
{
    case University = 'university';
    case Institution = 'institution';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::University => 'Universitate',
            self::Institution => 'Instituție publică',
            self::Company => 'Companie',
        };
    }

    /** The group's name, as a heading or a tag. */
    public function plural(): string
    {
        return match ($this) {
            self::University => 'Universități',
            self::Institution => 'Instituții publice',
            self::Company => 'Companii',
        };
    }

    /** Its block's id on /clienti. */
    public function anchor(): string
    {
        return match ($this) {
            self::University => 'universitati',
            self::Institution => 'institutii',
            self::Company => 'companii',
        };
    }
}
