<?php

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    $this->from('/')->post('/cerere-oferta', contactForm())
        ->assertRedirect(url('/').'#contact')
        ->assertSessionHas('contact-sent');

    $this->get('/')->assertSee('Mulțumim! Vă răspundem în aceeași zi lucrătoare.');
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
