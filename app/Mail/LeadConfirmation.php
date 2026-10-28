<?php

namespace App\Mail;

use App\Models\Lead;
use App\Settings\CompanySettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * To whoever filled in the contact form, when Setări › Cereri says so
 * (LeadSettings::$send_confirmation): their request has arrived, a copy of
 * it, and how to reach us. A reply goes to the company's address. Queued.
 */
final class LeadConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        $company = app(CompanySettings::class);

        return new Envelope(
            subject: 'Am primit mesajul tău · '.$company->name,
            replyTo: $company->email ? [new Address($company->email, $company->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.leads.confirmation',
            text: 'mail.leads.confirmation-text',
            with: ['company' => app(CompanySettings::class)],
        );
    }
}
