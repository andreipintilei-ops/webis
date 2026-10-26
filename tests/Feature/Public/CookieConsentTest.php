<?php

use App\Settings\AnalyticsSettings;
use App\Support\Consent\CookieConsent;
use App\Support\ReservedSlugs;
use App\Support\Seo\Sitemap;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
| Cookie consent: the banner, Google's consent signals, the tag that loads
| only after a yes, and the cookie policy (Support\Consent\CookieConsent).
*/

function setAnalytics(?string $ga4, ?string $ads = null): void
{
    $settings = app(AnalyticsSettings::class);
    $settings->ga4_measurement_id = $ga4;
    $settings->google_ads_id = $ads;
    $settings->save();
}

it('asks nothing while no optional tool is set', function () {
    setAnalytics(null);

    $html = $this->get('/politica-de-cookies/')->assertOk()->getContent();

    expect($html)->not->toContain('data-cookie-consent')
        ->not->toContain("gtag('consent'")
        ->not->toContain('data-cookie-settings')
        ->toContain('În prezent folosim doar cookie-urile necesare')
        ->toContain('webis_consent')
        ->not->toContain('_ga');
});

it('denies every Google signal by default and never renders the tag', function () {
    setAnalytics('G-TEST1234', 'AW-123456789');

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('data-cookie-consent')
        ->toContain('data-cookie-settings')
        ->toContain("ad_storage: 'denied'")
        ->toContain("analytics_storage: 'denied'")
        ->toContain("ad_user_data: 'denied'")
        ->toContain("ad_personalization: 'denied'")
        // gtag.js is added by the script after a yes, never in the markup.
        ->not->toContain('<script async src="https://www.googletagmanager.com');

    // The defaults come before the page's own scripts.
    expect(strpos($html, "gtag('consent', 'default'"))->toBeLessThan(strpos($html, '/build/'));
});

it('offers only the categories whose tool is set', function () {
    setAnalytics('G-TEST1234');

    $html = $this->get('/')->getContent();

    expect($html)->toContain('data-cookie-category="analytics"')
        ->not->toContain('data-cookie-category="marketing"')
        ->and(app(CookieConsent::class)->categories())->toBe(['analytics' => true, 'marketing' => false]);
});

it('hands the tag ids to the page only on the live site', function () {
    setAnalytics('G-TEST1234', 'AW-123456789');

    expect($this->get('/')->getContent())->toContain('"tags":null');

    app()->detectEnvironment(fn () => 'production');

    expect($this->asWritten()->get('https://www.webis.ro/')->getContent())
        ->toContain('"tags":{"ga4":"G-TEST1234","ads":"AW-123456789"}');
});

it('lists the cookies the site sets, by category', function () {
    setAnalytics('G-TEST1234', 'AW-123456789');

    $response = $this->get('/politica-de-cookies/')->assertOk();

    $response->assertSee('<title>Politica de cookies | Webis</title>', false)
        ->assertSee(config('session.cookie'))
        ->assertSee('XSRF-TOKEN')
        ->assertSee('webis_consent')
        ->assertSee('_ga_TEST1234')
        ->assertSee('_gcl_au')
        // The button that reopens the banner (its label is split into letters).
        ->assertSee('data-cookie-settings', false);
});

it('links the policy from the footer, and lists it in the sitemap', function () {
    expect(ReservedSlugs::contains('politica-de-cookies'))->toBeTrue();

    $this->get('/')->assertSee('href="/politica-de-cookies/"', false);

    Sitemap::forget();
    $this->get('/sitemap.xml')->assertSee('/politica-de-cookies/');
});

it('shows the verification codes set in the admin', function () {
    $settings = app(AnalyticsSettings::class);
    $settings->google_site_verification = 'abc123';
    $settings->save();

    $this->get('/')->assertSee('<meta name="google-site-verification" content="abc123">', false);
});

it('asks for each tool under its own category, naming it', function () {
    $settings = app(AnalyticsSettings::class);
    $settings->hotjar_site_id = '1064299';
    $settings->meta_pixel_id = '123456789012345';
    $settings->save();

    $html = $this->get('/')->getContent();

    expect($html)->toContain('data-cookie-category="analytics"')
        ->toContain('data-cookie-category="marketing"')
        ->toContain('Hotjar.')
        ->toContain('Meta Pixel.');

    $this->get('/politica-de-cookies/')
        ->assertSee('_hjSessionUser_1064299')
        ->assertSee('_fbp')
        ->assertSee('Meta Platforms Ireland Limited')
        ->assertDontSee('_gcl_au');
});

it('offers both categories for Tag Manager alone', function () {
    $settings = app(AnalyticsSettings::class);
    $settings->gtm_container_id = 'GTM-ABC1234';
    $settings->save();

    expect(app(CookieConsent::class)->categories())->toBe(['analytics' => true, 'marketing' => true])
        ->and(app(CookieConsent::class)->names('marketing'))->toBe(['Google Tag Manager']);
});

it('hands every tool and the lead conversion to the live site', function () {
    $settings = app(AnalyticsSettings::class);
    $settings->google_ads_id = 'AW-123456789';
    $settings->google_ads_lead_label = 'AbC_lead-1';
    $settings->linkedin_partner_id = '1234567';
    $settings->clarity_project_id = 'abcd1234ef';
    $settings->save();

    app()->detectEnvironment(fn () => 'production');

    expect($this->asWritten()->get('https://www.webis.ro/')->getContent())
        ->toContain('"tags":{"clarity":"abcd1234ef","ads":"AW-123456789","linkedin":"1234567","adsLead":"AW-123456789\/AbC_lead-1"}')
        // No tool's script in the markup: each is added after a yes.
        ->not->toContain('src="https://www.clarity.ms')
        ->not->toContain('src="https://snap.licdn.com');
});
