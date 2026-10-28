<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A new request from the contact form, to the addresses in Setări › Cereri
 * (LeadSettings): who wrote, how to answer, what they asked, and the lead in
 * the admin. A reply goes to the visitor. Queued: the form does not wait.
 */
final class LeadReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cerere nouă: '.collect([$this->lead->name, $this->lead->company])->filter()->implode(', '),
            replyTo: $this->lead->email ? [new Address($this->lead->email, $this->lead->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.leads.received',
            text: 'mail.leads.received-text',
            with: ['adminUrl' => route('admin.leads.show', $this->lead)],
        );
    }
}
