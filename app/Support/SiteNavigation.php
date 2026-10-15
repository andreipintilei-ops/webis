<?php

namespace App\Support;

use App\Enums\ProjectType;

/**
 * The public site's navigation, shared by every menu.
 *
 * `links()`, the sections:
 * - `bar`: shown in the top bar
 * - `menu`: a main section — the footer's page list
 * - `place`: "section" — in the full-screen menu, in the row of smaller
 *   links under the services
 * - `cta`: shown as the button (a pill) instead, with this label — the
 *   full-screen menu's one way to Contact
 * - `children`: a dropdown under the item in the top bar
 *
 * `services()`: the three services, the Servicii dropdown and the numbered
 * rows of the full-screen menu, each with its line of description (`blurb`).
 * `groups()`: the footer's columns of grouped links.
 * `legal()`: the small links at the menu's and the footer's foot.
 *
 * The group and legal links point at the CMS pages they will be (root slugs);
 * until a page exists at that slug its link 404s.
 */
final class SiteNavigation
{
    /**
     * What Webis does, in the order the home page's stage tells it.
     *
     * @var list<array{label: string, href: string, blurb: string}>
     */
    private const array SERVICES = [
        ['label' => 'Software la comandă', 'href' => '/software-la-comanda', 'blurb' => 'ERP, CRM, registratură și integrări, construite de la zero.'],
        ['label' => 'Produse SaaS', 'href' => '/produse', 'blurb' => 'Produse gata de folosit, pe abonament lunar.'],
        ['label' => 'Web și e-commerce', 'href' => '/web', 'blurb' => 'Site-uri de prezentare și magazine online.'],
    ];

    /**
     * @return list<array{label: string, href: string, bar: bool, menu: bool, place: string|null, cta: string|null, children: list<array{label: string, href: string, blurb?: string}>, current: bool}>
     */
    public static function links(bool $withHome = true): array
    {
        $links = [
            ['label' => 'Acasă', 'href' => '/', 'bar' => false, 'menu' => false, 'place' => null, 'cta' => null, 'children' => []],
            ['label' => 'Servicii', 'href' => '/solutii', 'bar' => true, 'menu' => true, 'place' => null, 'cta' => null, 'children' => self::SERVICES],
            ['label' => 'Clienți', 'href' => '/portofoliu', 'bar' => true, 'menu' => true, 'place' => 'section', 'cta' => null, 'children' => []],
            ['label' => 'Despre', 'href' => '/despre', 'bar' => true, 'menu' => true, 'place' => 'section', 'cta' => null, 'children' => []],
            ['label' => 'Blog', 'href' => '/blog', 'bar' => true, 'menu' => true, 'place' => 'section', 'cta' => null, 'children' => []],
            ['label' => 'Contact', 'href' => '/contact', 'bar' => true, 'menu' => false, 'place' => null, 'cta' => 'Hai să discutăm', 'children' => []],
        ];

        if (! $withHome) {
            array_shift($links);
        }

        return array_map(self::mark(...), $links);
    }

    /**
     * The three services, marked like the rest.
     *
     * @return list<array{label: string, href: string, blurb: string, current: bool}>
     */
    public static function services(): array
    {
        return array_map(self::mark(...), self::SERVICES);
    }

    /**
     * The footer's grouped links.
     *
     * @return list<array{label: string, links: list<array{label: string, href: string, blurb?: string}>}>
     */
    public static function groups(): array
    {
        return [
            ['label' => 'Servicii', 'links' => self::SERVICES],
            // The client work, by type (the filter on /portofoliu).
            ['label' => 'Portofoliu', 'links' => [
                ['label' => 'Toate proiectele', 'href' => '/portofoliu'],
                ...array_map(fn (ProjectType $type): array => [
                    'label' => $type->plural(),
                    'href' => '/portofoliu?tip='.$type->value,
                ], ProjectType::cases()),
            ]],
            ['label' => 'Web și e-commerce', 'links' => [
                ['label' => 'Creare site de prezentare', 'href' => '/creare-site-de-prezentare'],
                ['label' => 'Magazin online', 'href' => '/magazin-online'],
            ]],
        ];
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    public static function legal(): array
    {
        return [
            ['label' => 'Confidențialitate', 'href' => '/confidentialitate'],
            ['label' => 'Accesibilitate', 'href' => '/accesibilitate'],
        ];
    }

    /**
     * @param  array<string, mixed>  $link
     * @return array<string, mixed>
     */
    private static function mark(array $link): array
    {
        $path = self::path();
        $href = (string) $link['href'];

        return [
            ...$link,
            'current' => match (true) {
                $href === '/' => $path === '/',
                // A project's page belongs to the portfolio.
                $href === '/portofoliu' => str_starts_with($path, $href) || str_starts_with($path, '/proiect/'),
                default => str_starts_with($path, $href),
            },
        ];
    }

    private static function path(): string
    {
        return '/'.ltrim(request()->path(), '/');
    }
}
