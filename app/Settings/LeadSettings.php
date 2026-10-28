<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Who hears about new quote requests (App\Mail\LeadReceived), and whether
 * the visitor gets a confirmation (App\Mail\LeadConfirmation).
 */
class LeadSettings extends Settings
{
    /**
     * Addresses notified of every new (non-spam) lead.
     *
     * @phpstan-var list<string>
     */
    public array $notification_emails;

    /** Whether the visitor gets a Romanian confirmation email. */
    public bool $send_confirmation;

    public static function group(): string
    {
        return 'leads';
    }
}
