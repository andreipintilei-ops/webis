<?php

use App\Enums\LeadStatus;
use App\Enums\PageType;
use App\Mail\LeadConfirmation;
use App\Mail\LeadReceived;
use App\Models\Lead;
use App\Models\Page;
use App\Settings\LeadSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function contactForm(array $overrides = []): array
{
    return [
        'name' => 'Ana Popescu',
        'organization' => 'Universitatea X',
        'email' => 'ana@example.ro',
        'phone' => '0740 000 000',
        'message' => 'Avem nevoie de un sistem pentru proiecte.',
        ...$overrides,
    ];
}

it('stores a request as a new lead, with its organization and consent', function () {
    $this->withHeader('referer', 'https://webis.test/')
        ->postJson('/cerere-oferta', contactForm())
        ->assertCreated()
        ->assertJson(['sent' => true]);

    $lead = Lead::sole();

    expect($lead->status)->toBe(LeadStatus::New)
        ->and($lead->company)->toBe('Universitatea X')
        ->and($lead->consent_at)->not->toBeNull()
        ->and($lead->source_url)->toBe('https://webis.test/');
});

it('comes back to the form with a thank-you without JavaScript', function () {
    Page::factory()->ofType(PageType::Contact)->create(['title' => 'Contact', 'slug' => 'contact']);

    $this->from('/contact')->post('/cerere-oferta', contactForm())
        ->assertRedirect(url('/contact').'#contact')
        ->assertSessionHas('contact-sent');

    $this->get('/contact')->assertSee('Mulțumim! Îți răspundem cât de curând.');
});

it('needs a name, a message and one way to answer', function () {
    $this->postJson('/cerere-oferta', contactForm(['name' => '', 'message' => '', 'email' => '', 'phone' => '']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'message', 'email', 'phone']);

    // A phone alone is enough.
    $this->postJson('/cerere-oferta', contactForm(['email' => '']))->assertCreated();

    expect(Lead::count())->toBe(1);
});

it('keeps what the honeypot catches, as spam', function () {
    $this->postJson('/cerere-oferta', contactForm(['website' => 'https://spam.example']))->assertCreated();

    expect(Lead::sole()->status)->toBe(LeadStatus::Spam);
});

it('emails a new lead to the addresses in the settings, a reply going to the visitor', function () {
    Mail::fake();
    $leads = app(LeadSettings::class);
    $leads->notification_emails = ['office@webis.ro', 'andrei@webis.ro'];
    $leads->send_confirmation = false;
    $leads->save();

    $this->postJson('/cerere-oferta', contactForm())->assertCreated();

    $lead = Lead::sole();

    Mail::assertQueued(LeadReceived::class, fn (LeadReceived $mail) => $mail->lead->is($lead)
        && $mail->hasTo('office@webis.ro') && $mail->hasTo('andrei@webis.ro')
        && $mail->hasReplyTo('ana@example.ro'));
    Mail::assertNotQueued(LeadConfirmation::class);

    $html = (new LeadReceived($lead))->render();

    expect($html)->toContain('Universitatea X')
        ->toContain('Avem nevoie de un sistem pentru proiecte.')
        ->toContain(route('admin.leads.show', $lead));
});

it('sends the visitor a confirmation when that is on and there is an address to send it to', function () {
    Mail::fake();
    $leads = app(LeadSettings::class);
    $leads->notification_emails = [];
    $leads->send_confirmation = true;
    $leads->save();

    $this->postJson('/cerere-oferta', contactForm())->assertCreated();
    // A phone alone: no one to confirm to.
    $this->postJson('/cerere-oferta', contactForm(['email' => '']))->assertCreated();

    Mail::assertQueued(LeadConfirmation::class, 1);
    Mail::assertQueued(LeadConfirmation::class, fn (LeadConfirmation $mail) => $mail->hasTo('ana@example.ro'));
    Mail::assertNotQueued(LeadReceived::class);

    expect((new LeadConfirmation(Lead::first()))->render())->toContain('Bună, Ana!');
});

it('tells no one of spam', function () {
    Mail::fake();
    $leads = app(LeadSettings::class);
    $leads->notification_emails = ['office@webis.ro'];
    $leads->send_confirmation = true;
    $leads->save();

    $this->postJson('/cerere-oferta', contactForm(['website' => 'https://spam.example']))->assertCreated();

    Mail::assertNothingQueued();
});

it('keeps what a visitor typed as text in the email, not markup', function () {
    $lead = Lead::factory()->create([
        'name' => '<b>Ana</b>',
        'message' => "Salut <a href=\"https://evil.example\">aici</a>\nrândul doi",
    ]);

    expect((new LeadReceived($lead))->render())
        ->not->toContain('<a href="https://evil.example"')
        ->toContain('&lt;a href=&quot;https://evil.example&quot;&gt;')
        ->not->toContain('<b>Ana</b>');
});
