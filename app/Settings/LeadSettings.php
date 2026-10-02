<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Who hears about new quote requests, and the options the form offers.
 */
class LeadSettings extends Settings
{
    /**
     * Addresses notified of every new (non-spam) lead.
     *
     * @phpstan-var list<string>
     */
    public array $notification_emails;

    /**
     * Budget ranges offered in the quote form, in display order.
     *
     * @phpstan-var list<string>
     */
    public array $budget_options;

    /** Whether the visitor gets a Romanian confirmation email. */
    public bool $send_confirmation;

    public static function group(): string
    {
        return 'leads';
    }
}
