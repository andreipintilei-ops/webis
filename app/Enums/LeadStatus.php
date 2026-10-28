<?php

namespace App\Enums;

/**
 * The quote-request pipeline. Spam is stored rather than dropped so false
 * positives can be recovered from the admin inbox.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case OfferSent = 'offer_sent';
    case Won = 'won';
    case Lost = 'lost';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouă',
            self::Contacted => 'Contactat',
            self::OfferSent => 'Ofertă trimisă',
            self::Won => 'Câștigat',
            self::Lost => 'Pierdut',
            self::Spam => 'Spam',
        };
    }
}
