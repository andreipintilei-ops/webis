<?php

namespace App\Support\Seo;

use App\Models\Asset;
use App\Models\Page;
use App\Settings\CompanySettings;
use App\Settings\SeoSettings;
use App\Support\Brand;
use App\Support\PhoneLink;
use App\Support\PublicUrls;
use Illuminate\Support\Str;

/**
 * The JSON-LD the public layout writes in <head>, from the settings (company
 * details, SEO, brand), never hard-coded per view:
 *
 * - every page: the Organization and the WebSite — with what tells this
 *   company apart from others of a like name: its legal name, founding
 *   date, CUI and Reg. Com. number (when set);
 * - the home page and /contact/: the Organization is a ProfessionalService
 *   too (a LocalBusiness: its address, coordinates, hours) — one node, both
 *   types, so the two are the same entity (one @id);
 * - internal pages: a BreadcrumbList, Acasă › section › page;
 * - articles: a BlogPosting.
 *
 * Left out on purpose: ratings and reviews (Google ignores a business's own
 * markup of them), FAQPage, and SoftwareApplication/Product for the SaaS
 * products (they would need prices or ratings the site does not show).
 */
final class StructuredData
{
    /**
     * The section a path's first segment belongs to: its breadcrumb. The
     * industry pages, at /servicii/{slug}/, are listed at
     * /realizare-site-uri-tematice/.
     */
    private const array SECTIONS = [
        'servicii' => ['Site-uri tematice', '/realizare-site-uri-tematice/'],
        'proiect' => ['Portofoliu', '/portofoliu/'],
        'blog' => ['Blog', '/blog/'],
    ];

    public function __construct(
        private readonly CompanySettings $company,
        private readonly SeoSettings $seo,
        private readonly Brand $brand,
    ) {}

    public static function home(): string
    {
        return config('site.canonical_url').'/';
    }

    public static function organizationId(): string
    {
        return self::home().'#organization';
    }

    /**
     * @param  list<array<string, mixed>>  $extra  nodes of the page's own (a BlogPosting)
     * @param  list<array{0: string, 1: string}>|null  $breadcrumbs  [name, URL] each, Acasă first; null for none
     * @return array<string, mixed>
     */
    public function graph(bool $localBusiness = false, ?array $breadcrumbs = null, array $extra = []): array
    {
        $graph = [$this->organization($localBusiness), $this->website()];

        if ($breadcrumbs !== null && count($breadcrumbs) > 1) {
            $graph[] = $this->breadcrumbList($breadcrumbs);
        }

        return ['@context' => 'https://schema.org', '@graph' => [...$graph, ...$extra]];
    }

    /**
     * Acasă, the section the path sits in (Site-uri tematice, Portofoliu, Blog), then
     * the page itself — or `$parent` in the section's place (an article,
     * at the site root, under Blog).
     *
     * @param  array{0: string, 1: string}|null  $parent  [name, path]
     * @return list<array{0: string, 1: string}>
     */
    public static function breadcrumbs(string $path, string $name, ?array $parent = null): array
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        if ($segments === []) {
            return [];
        }

        $crumbs = [['Acasă', self::home()]];
        $section = count($segments) > 1 ? (self::SECTIONS[$segments[0]] ?? null) : null;

        if ($parent ?? $section) {
            [$label, $at] = $parent ?? $section;
            $crumbs[] = [$label, PublicUrls::canonical($at)];
        }

        $crumbs[] = [$name, PublicUrls::canonical($path)];

        return $crumbs;
    }

    /**
     * An article's BlogPosting.
     *
     * @return array<string, mixed>
     */
    public function blogPosting(Page $page, string $headline, string $url, ?string $image): array
    {
        return array_filter([
            '@type' => 'BlogPosting',
            '@id' => $url.'#article',
            'headline' => Str::limit($headline, 107, '…'),
            'datePublished' => $page->published_at?->toIso8601String(),
            'dateModified' => ($page->updated_at ?? $page->published_at)?->toIso8601String(),
            'author' => ['@type' => 'Organization', '@id' => self::organizationId(), 'name' => $this->company->name, 'url' => self::home()],
            'publisher' => ['@id' => self::organizationId()],
            'image' => $image ?? $this->shareImage()['url'] ?? $this->logo()['url'] ?? null,
            'mainEntityOfPage' => $url,
            'inLanguage' => 'ro-RO',
        ], fn ($value) => $value !== null);
    }

    /**
     * The default share image (SEO settings), 1200×630.
     *
     * @return array{url: string, width: int|null, height: int|null}|null
     */
    public function shareImage(): ?array
    {
        return self::image(Asset::with('media')->find($this->seo->default_og_image_asset_id));
    }

    /**
     * @return array{url: string, width: int|null, height: int|null}|null
     */
    public static function image(?Asset $asset): ?array
    {
        $file = $asset?->file();

        return $file === null ? null : ['url' => $file->getUrl(), 'width' => $asset->width, 'height' => $asset->height];
    }

    /**
     * @return array<string, mixed>
     */
    private function organization(bool $localBusiness): array
    {
        $logo = $this->logo();
        $node = [
            '@type' => $localBusiness ? ['Organization', 'ProfessionalService'] : 'Organization',
            '@id' => self::organizationId(),
            'name' => $this->company->name,
            'legalName' => $this->company->legal_name,
            'foundingDate' => $this->company->founding_date,
            ...$this->registration(),
            'url' => self::home(),
            'logo' => $logo === null ? null : array_filter(['@type' => 'ImageObject', 'url' => $logo['url'], 'width' => $logo['width'], 'height' => $logo['height']]),
            'email' => $this->company->email,
            'telephone' => $this->telephone(),
            'sameAs' => array_values(array_filter($this->company->social)) ?: null,
        ];

        if ($localBusiness) {
            $node += [
                'image' => $this->shareImage()['url'] ?? $logo['url'] ?? null,
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $this->company->street_address,
                    'addressLocality' => $this->company->locality,
                    // Only when the site shows one: never guessed.
                    'postalCode' => $this->company->postal_code,
                    'addressCountry' => $this->company->country_code,
                ]),
                'geo' => $this->company->latitude !== null && $this->company->longitude !== null
                    ? ['@type' => 'GeoCoordinates', 'latitude' => $this->company->latitude, 'longitude' => $this->company->longitude]
                    : null,
                'areaServed' => $this->company->country_code,
                'openingHoursSpecification' => $this->openingHours() ?: null,
            ];
        }

        return array_filter($node, fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::home().'#website',
            'url' => self::home(),
            'name' => $this->company->name,
            'inLanguage' => 'ro-RO',
            'publisher' => ['@id' => self::organizationId()],
        ];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $crumbs
     * @return array<string, mixed>
     */
    private function breadcrumbList(array $crumbs): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $crumb, int $i): array => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb[0],
                'item' => $crumb[1],
            ], $crumbs, array_keys($crumbs)),
        ];
    }

    /**
     * The organization's logo: the SEO settings' own, else the brand's square
     * icon (Google wants at least 112×112), else the wordmark.
     *
     * @return array{url: string, width: int|null, height: int|null}|null
     */
    private function logo(): ?array
    {
        return self::image(Asset::with('media')->find($this->seo->logo_asset_id))
            ?? $this->brand->icon()
            ?? $this->brand->logo();
    }

    /**
     * The company's registration, as the footer shows it: the CUI as its tax
     * id — and as its VAT id when it carries the RO of a VAT payer — and the
     * Reg. Com. number as an identifier of its own.
     *
     * @return array<string, mixed>
     */
    private function registration(): array
    {
        $cui = $this->company->vat_id;
        $registry = $this->company->registration_number;

        return array_filter([
            'taxID' => $cui,
            'vatID' => $cui !== null && Str::startsWith(Str::upper($cui), 'RO') ? $cui : null,
            'identifier' => $registry === null ? null : [
                '@type' => 'PropertyValue',
                'propertyID' => 'Registrul Comerțului',
                'value' => $registry,
            ],
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function telephone(): ?string
    {
        $href = PhoneLink::href($this->company->phone);

        return $href === null ? null : Str::after($href, 'tel:');
    }

    /**
     * The opening hours as the company settings hold them, only when set (the
     * site shows them then).
     *
     * @return list<array<string, mixed>>
     */
    private function openingHours(): array
    {
        $days = ['Mo' => 'Monday', 'Tu' => 'Tuesday', 'We' => 'Wednesday', 'Th' => 'Thursday', 'Fr' => 'Friday', 'Sa' => 'Saturday', 'Su' => 'Sunday'];

        return array_values(array_filter(array_map(function (array $row) use ($days): ?array {
            $dayNames = array_values(array_filter(array_map(fn ($day) => $days[$day] ?? null, $row['days'] ?? [])));

            return $dayNames !== [] && ! empty($row['opens']) && ! empty($row['closes'])
                ? ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $dayNames, 'opens' => $row['opens'], 'closes' => $row['closes']]
                : null;
        }, $this->company->opening_hours)));
    }
}
