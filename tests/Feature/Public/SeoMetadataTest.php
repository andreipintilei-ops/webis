<?php

use App\Enums\PageType;
use App\Enums\ProjectType;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Page;
use App\Models\PostCategory;
use App\Models\Project;
use App\Settings\CompanySettings;
use App\Settings\SeoSettings;
use App\Support\Seo\SeoAudit;
use App\Support\Seo\SeoText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/*
| Titles and descriptions, structured data, image alt texts and indexing:
| what search engines and link previews read of the site.
*/

/** Content of every kind the sitemap lists, the home page with its own SEO. */
function seoSite(): void
{
    Page::factory()->ofType(PageType::Home)->create([
        'slug' => 'acasa',
        'seo' => ['title' => 'Web design Iași, creare site și software la comandă', 'description' => 'Web design în Iași: site-uri de prezentare și magazine online cu SEO de la început, plus software la comandă și aplicații SaaS.'],
    ]);
    Page::factory()->ofType(PageType::Contact)->create(['slug' => 'contact', 'title' => 'Contact']);
    Page::factory()->ofType(PageType::Legal)->create(['slug' => 'politica-de-confidentialitate', 'title' => 'Politica de confidentialitate']);
    Page::factory()->create(['slug' => 'creare-magazin-online', 'title' => 'Creare magazin online', 'blocks' => [
        ['id' => 'h', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'split', 'heading' => 'Ai nevoie de un magazin online?', 'subheading' => 'Oferim soluții personalizate și ușor de utilizat pentru a transforma visul tău de afacere online în realitate.']],
    ]]);
    Page::factory()->ofType(PageType::Industry)->create(['slug' => 'creare-site-stomatologie', 'title' => 'Creare site stomatologie', 'blocks' => [
        ['id' => 'h', 'type' => 'hero', 'v' => 1, 'data' => ['layout' => 'split', 'heading' => 'Creare site stomatologie', 'subheading' => 'Prin serviciul de creare site stomatologie, veti obtine un site modern si inovator pentru prezentarea activitatii cabinetului dumneavoastra stomatologic, cu programari online si galerie.']],
    ]]);

    $seo = PostCategory::factory()->create(['slug' => 'seo', 'name' => 'SEO']);
    Page::factory()->ofType(PageType::Article)->count(3)
        ->sequence(
            ['slug' => 'articol-1', 'title' => 'Ce reprezinta limbajele de programare client-side si server-side si diferentele dintre ele'],
            ['slug' => 'articol-2', 'title' => 'Ce este SEO & SEO On-Page si Importanta acestora'],
            ['slug' => 'articol-3', 'title' => 'Cum să îți transformi afacerea de pensiune sau hotel într-un succes online cu un site puternic'],
        )
        ->create(['post_category_id' => $seo->id]);

    // The share image (SEO settings), as the site has.
    Storage::fake('public');
    $share = app(SeoSettings::class);
    $share->default_og_image_asset_id = Asset::factory()->withImage(1200, 630)->create()->id;
    $share->save();

    // One client's site and logo: the same name, told apart by their type.
    Project::factory()->create(['slug' => 'zyanya', 'title' => 'Zyanya', 'type' => ProjectType::Shop]);
    Project::factory()->create(['slug' => 'zyanya-logo', 'title' => 'Zyanya', 'type' => ProjectType::Logo]);
}

/** @return array<string, mixed> the page's JSON-LD graph, by @type */
function jsonLdOf(string $html): array
{
    preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $match);
    $graph = json_decode($match[1] ?? '{}', true)['@graph'] ?? [];

    return collect($graph)->keyBy(fn (array $node): string => implode('+', (array) $node['@type']))->all();
}

it('gives every URL in the sitemap a unique title and description within the limits', function () {
    seoSite();

    $this->artisan('seo:fill-missing', [
        '--map' => storage_path('framework/testing/no-url-map.csv'),
        // Never the real list of values to review.
        '--review' => storage_path('framework/testing/seo-generated.csv'),
    ])->assertSuccessful();

    $rows = app(SeoAudit::class)->run();
    $failing = array_filter($rows, fn (array $row): bool => $row['problems'] !== []);

    expect(count($rows))->toBeGreaterThan(10)
        ->and($failing)->toBe([]);
});

it('fails the audit on a missing description, a long title and "organizații"', function () {
    seoSite();
    Page::factory()->create(['slug' => 'pagina', 'excerpt' => null, 'seo' => ['title' => str_repeat('Titlu foarte lung ', 5)]]);
    Page::factory()->create(['slug' => 'alta', 'seo' => ['title' => 'Pentru organizații', 'description' => 'Aplicații pentru organizații.']]);

    $problems = collect(app(SeoAudit::class)->run())->keyBy('path')->map->problems;

    expect($problems['/pagina/'])->toContain('no description')
        ->and(implode(' ', $problems['/pagina/']))->toContain('title 97 characters')
        ->and($problems['/alta/'])->toContain('"organizați…"');
});

it('holds titles and descriptions to their limits, cut at a word', function () {
    $title = SeoText::title('Ce este un LOGO si Diferenta dintre un Vector Logo & Bitmap Logo - Webis.ro', ' | Webis');
    $description = SeoText::description(str_repeat('Un site rapid și ușor de găsit în Google pentru afacerea ta din Iași. ', 4));

    // Ended at the "&" (the last break that fits), the site's old suffix off.
    expect($title)->toBe('Ce este un LOGO si Diferenta dintre un Vector Logo')
        ->and(mb_strlen($title.' | Webis'))->toBeLessThanOrEqual(60)
        ->and(SeoText::title('Ce sunt cadrele frame/iframe? De ce sa nu le folosim pe un site web', ' | Webis'))->toBe('Ce sunt cadrele frame/iframe?')
        ->and(mb_strlen($description))->toBeLessThanOrEqual(155)
        // Whole sentences while they fit.
        ->and($description)->toEndWith('Iași.')
        ->and(SeoText::description('Logo-ul Geprocon S.A. a fost creat pentru a reflecta seriozitatea firmei.'))->toBe('Logo-ul Geprocon S.A. a fost creat pentru a reflecta seriozitatea firmei.');
});

it('writes the home page\'s title, description and Open Graph tags', function () {
    seoSite();

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>Web design Iași, creare site și software la comandă | Webis</title>')
        ->toContain('<meta name="description" content="Web design în Iași: site-uri de prezentare și magazine online cu SEO de la început, plus software la comandă și aplicații SaaS.">')
        ->toContain('<meta property="og:title" content="Web design Iași, creare site și software la comandă | Webis">')
        ->toContain('<meta property="og:description" content="Web design în Iași')
        ->toContain('<meta property="og:url" content="https://www.webis.ro/">');
});

it('describes the business on the home page and /contact/, without ratings', function () {
    seoSite();

    foreach (['/', '/contact/'] as $path) {
        $html = $this->asWritten()->get($path)->assertOk()->getContent();
        $nodes = jsonLdOf($html);
        $business = $nodes['Organization+ProfessionalService'];

        expect($business['@id'])->toBe('https://www.webis.ro/#organization')
            ->and($business['legalName'])->not->toBeEmpty()
            ->and($business['address'])->toMatchArray(['@type' => 'PostalAddress', 'addressCountry' => 'RO'])
            ->and($business['areaServed'])->toBe('RO')
            ->and($nodes['WebSite']['publisher']['@id'])->toBe('https://www.webis.ro/#organization')
            ->and($html)->not->toContain('aggregateRating')
            ->not->toContain('"review"');
    }
});

it('tells the company apart from any of a like name: its registration, founding and place', function () {
    seoSite();
    $company = app(CompanySettings::class);
    $company->vat_id = 'RO12345678';
    $company->registration_number = 'J22/1234/2016';
    $company->street_address = 'B-dul Poitiers 16';
    $company->postal_code = '700671';
    $company->latitude = 47.1355;
    $company->longitude = 27.5925;
    $company->save();

    $html = $this->asWritten()->get('/')->assertOk()->getContent();
    $business = jsonLdOf($html)['Organization+ProfessionalService'];
    $elsewhere = jsonLdOf($this->asWritten()->get('/servicii/creare-site-stomatologie/')->getContent())['Organization'];

    expect($business)->toMatchArray([
        'foundingDate' => '2016',
        'taxID' => 'RO12345678',
        'vatID' => 'RO12345678',
        'identifier' => ['@type' => 'PropertyValue', 'propertyID' => 'Registrul Comerțului', 'value' => 'J22/1234/2016'],
        'geo' => ['@type' => 'GeoCoordinates', 'latitude' => 47.1355, 'longitude' => 27.5925],
    ])
        ->and($business['address']['postalCode'])->toBe('700671')
        // The registration on every page; the place where it is a business.
        ->and($elsewhere)->toHaveKeys(['foundingDate', 'taxID', 'identifier'])
        ->not->toHaveKey('geo')
        // And in plain text, in the footer.
        ->and($html)->toContain('· CUI RO12345678')
        ->toContain('· Reg. Com. J22/1234/2016')
        ->toContain('· B-dul Poitiers 16, Iași');
});

it('gives no VAT id for a CUI without the RO of a VAT payer', function () {
    seoSite();
    $company = app(CompanySettings::class);
    $company->vat_id = '12345678';
    $company->save();

    $business = jsonLdOf($this->asWritten()->get('/')->getContent())['Organization+ProfessionalService'];

    expect($business['taxID'])->toBe('12345678')->and($business)->not->toHaveKey('vatID');
});

it('describes the organization everywhere, an internal page\'s path and an article', function () {
    seoSite();

    $industry = jsonLdOf($this->asWritten()->get('/servicii/creare-site-stomatologie/')->assertOk()->getContent());
    $article = jsonLdOf($this->asWritten()->get('/articol-1/')->assertOk()->getContent());

    expect($industry)->toHaveKey('Organization')
        ->not->toHaveKey('Organization+ProfessionalService')
        ->and(array_column($industry['BreadcrumbList']['itemListElement'], 'name'))->toBe(['Acasă', 'Site-uri tematice', 'Creare site stomatologie'])
        ->and(array_column($industry['BreadcrumbList']['itemListElement'], 'item')[1])->toBe('https://www.webis.ro/realizare-site-uri-tematice/')
        ->and($article['BlogPosting'])->toHaveKeys(['headline', 'datePublished', 'dateModified', 'author', 'image', 'mainEntityOfPage'])
        ->and($article['BlogPosting']['author']['@id'])->toBe('https://www.webis.ro/#organization')
        ->and(array_column($article['BreadcrumbList']['itemListElement'], 'name')[1])->toBe('Blog');
});

it('marks up no FAQ and no product', function (string $path) {
    expect($this->asWritten()->get($path)->assertOk()->getContent())
        ->not->toContain('FAQPage')
        ->not->toContain('SoftwareApplication')
        ->not->toContain('"Product"');
})->with(['/produse/', '/software-la-comanda/']);

it('names every client logo in its alt text', function () {
    seoSite();
    Client::factory()->create(['name' => 'Veepee', 'logo_asset_id' => Asset::factory()->withImage(200, 80)->create(['alt' => null])->id]);

    $html = $this->get('/')->assertOk()->getContent();
    preg_match('#<section id="clienti" class="logo-wall.*?</section>#s', $html, $wall);
    preg_match_all('/<img\b[^>]*>/', $wall[0], $images);

    expect($images[0])->not->toBeEmpty();

    foreach ($images[0] as $image) {
        expect($image)->toMatch('/\balt="[^"]+"/');
    }
});

describe('indexing', function () {
    it('keeps every page of a non-production site out of search engines', function (string $path) {
        seoSite();

        $response = $this->asWritten()->get($path)->assertOk();

        expect($response->getContent())->toContain('<meta name="robots" content="noindex, nofollow">')
            ->and($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow');
    })->with(['/', '/servicii/creare-site-stomatologie/', '/articol-1/', '/proiect/zyanya/', '/web/', '/blog/']);

    it('lets search engines index the public pages in production on the canonical host', function (string $path) {
        seoSite();
        app()->detectEnvironment(fn () => 'production');

        $response = $this->asWritten()->get('https://www.webis.ro'.$path)->assertOk();

        expect($response->getContent())->not->toContain('name="robots"')
            ->and($response->headers->has('X-Robots-Tag'))->toBeFalse();
    })->with(['/', '/servicii/creare-site-stomatologie/', '/articol-1/', '/proiect/zyanya/', '/web/', '/blog/', '/contact/']);

    it('keeps production reached at another host out of search engines', function () {
        seoSite();
        app()->detectEnvironment(fn () => 'production');

        $response = $this->get('https://staging.webis.ro/')->assertOk();

        expect($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow')
            ->and($response->getContent())->toContain('<meta name="robots" content="noindex, nofollow">');
    });

    it('keeps the login and the admin out of search engines, in production too', function () {
        app()->detectEnvironment(fn () => 'production');

        expect($this->get('https://www.webis.ro/login')->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow')
            ->and($this->get('https://www.webis.ro/admin')->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow');
    });
});
