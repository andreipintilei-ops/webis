<?php

use App\Enums\PageType;
use App\Models\Page;
use App\Settings\CompanySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders a published contact page with the shared form and company details', function () {
    Page::factory()->ofType(PageType::Contact)->create(['title' => 'Contact', 'slug' => 'contact']);

    $company = app(CompanySettings::class);
    $company->phone = '+40 740 123 456';
    $company->email = 'contact@example.ro';
    $company->street_address = 'Strada Exemplu 10';
    $company->locality = 'Iași';
    $company->save();

    $this->get('/contact')
        ->assertOk()
        ->assertViewIs('public.contact')
        ->assertSee('<title>Contact | Webis</title>', escape: false)
        ->assertSee('despre proiectul tău.')
        ->assertSee('data-contact-form', escape: false)
        ->assertSee('action="'.route('contact.store').'"', escape: false)
        ->assertSee('href="tel:+40740123456"', escape: false)
        ->assertSee('href="mailto:contact@example.ro"', escape: false)
        ->assertSee('Strada Exemplu 10, Iași')
        ->assertSee('Ce urmează după mesaj')
        ->assertDontSee('site-footer__cta', escape: false)
        ->assertDontSee('chapter--light', escape: false);

    $this->get('/realizare-site-uri-tematice')->assertOk()->assertSee('site-footer__cta', escape: false);
});

it('shows where the office is: a lite map, nothing from Google until asked for, and a link to the place', function () {
    Page::factory()->ofType(PageType::Contact)->create(['title' => 'Contact', 'slug' => 'contact']);

    $company = app(CompanySettings::class);
    $company->street_address = 'B-dul Poitiers 16';
    $company->locality = 'Iași';
    $company->google_reviews_url = 'https://search.google.com/local/reviews?placeid=ChIJdb43T2j6ykARljUR4Ws4GEI';
    $company->save();

    $html = $this->get('/contact')->assertOk()->getContent();
    preg_match('#<div class="contact-page__map">.*?</iframe>\s*</div>\s*</div>#s', $html, $map);

    expect($map[0])
        ->toContain('Unde ne găsești')
        // The map itself, always there, loaded as it is scrolled near.
        ->toContain('<iframe src="https://maps.google.com/maps?q=Webis%2C+B-dul+Poitiers+16%2C+Ia%C8%99i&amp;z=16&amp;hl=ro&amp;output=embed"')
        ->toContain('loading="lazy"')
        // The link lands on the business itself, its place behind the reviews.
        ->toContain('href="https://www.google.com/maps/search/?api=1&amp;query=Webis%2C+B-dul+Poitiers+16%2C+Ia%C8%99i&amp;query_place_id=ChIJdb43T2j6ykARljUR4Ws4GEI"')
        ->not->toContain('Arată harta');
});

it('keeps the contact template and redirects when a contact page is renamed', function () {
    $page = Page::factory()->ofType(PageType::Contact)->create(['slug' => 'contact']);
    $page->update(['slug' => 'discutam']);

    $this->get('/contact')->assertRedirect('/discutam')->assertStatus(301);
    $this->get('/discutam')->assertOk()->assertViewIs('public.contact');
});

it('restores form values and accessible validation errors without JavaScript', function () {
    Page::factory()->ofType(PageType::Contact)->create(['slug' => 'contact']);

    $response = $this->followingRedirects()->from('/contact')->post('/cerere-oferta', [
        'name' => 'Ana Popescu',
        'email' => 'invalid-email',
        'message' => 'Vrem o platformă pentru echipa noastră.',
    ]);

    $response
        ->assertOk()
        ->assertSee('value="Ana Popescu"', escape: false)
        ->assertSee('value="invalid-email"', escape: false)
        ->assertSee('aria-invalid="true"', escape: false)
        ->assertSee('Vrem o platformă pentru echipa noastră.');
});
