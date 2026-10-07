<?php

namespace App\Support;

/**
 * The public site's navigation, shared by every menu.
 *
 * `links()`, the sections:
 * - `tiny`: the small word the full-screen menu sets under a section
 * - `bar`: shown in the top bar
 * - `menu`: shown in the full-screen menu's section list
 * - `cta`: shown as the button (a pill) instead, with this label
 * - `children`: a dropdown under the item in the top bar
 *
 * `groups()`: the full-screen menu's side column of grouped links.
 * `legal()`: the small links at the menu's foot.
 *
 * The group and legal links point at the CMS pages they will be (root slugs);
 * until a page exists at that slug its link 404s.
 */
final class SiteNavigation
{
    /**
     * What Webis builds: the Soluții dropdown and the menu's first group.
     *
     * @var list<array{label: string, href: string}>
     */
    private const array SOLUTIONS = [
        ['label' => 'Software la comandă', 'href' => '/software-la-comanda'],
        ['label' => 'Platforme', 'href' => '/platforme'],
        ['label' => 'Mentenanță și suport', 'href' => '/mentenanta-si-suport'],
    ];

    /**
     * @return list<array{label: string, href: string, tiny: string|null, bar: bool, menu: bool, cta: string|null, children: list<array{label: string, href: string}>, current: bool}>
     */
    public static function links(bool $withHome = true): array
    {
        $links = [
            ['label' => 'Acasă', 'href' => '/', 'tiny' => null, 'bar' => false, 'menu' => false, 'cta' => null, 'children' => []],
            ['label' => 'Soluții', 'href' => '/solutii', 'tiny' => 'ce facem', 'bar' => true, 'menu' => true, 'cta' => null, 'children' => self::SOLUTIONS],
            ['label' => 'Clienți', 'href' => '/clienti', 'tiny' => 'cu cine lucrăm', 'bar' => true, 'menu' => true, 'cta' => null, 'children' => []],
            ['label' => 'Produse', 'href' => '/produse', 'tiny' => 'ale noastre', 'bar' => true, 'menu' => true, 'cta' => null, 'children' => []],
            ['label' => 'Despre', 'href' => '/despre', 'tiny' => 'cine suntem', 'bar' => true, 'menu' => true, 'cta' => null, 'children' => []],
            ['label' => 'Blog', 'href' => '/blog', 'tiny' => 'idei', 'bar' => false, 'menu' => false, 'cta' => null, 'children' => []],
            ['label' => 'Contact', 'href' => '/contact', 'tiny' => 'hai să vorbim', 'bar' => true, 'menu' => false, 'cta' => 'Hai să discutăm', 'children' => []],
        ];

        if (! $withHome) {
            array_shift($links);
        }

        $path = self::path();
        $marked = [];

        foreach ($links as $link) {
            $marked[] = [
                ...$link,
                'current' => $link['href'] === '/' ? $path === '/' : str_starts_with($path, $link['href']),
            ];
        }

        return $marked;
    }

    /**
     * The full-screen menu's grouped links.
     *
     * @return list<array{label: string, links: list<array{label: string, href: string}>}>
     */
    public static function groups(): array
    {
        return [
            ['label' => 'Soluții', 'links' => self::SOLUTIONS],
            ['label' => 'Pentru cine', 'links' => [
                ['label' => 'Universități', 'href' => '/universitati'],
                ['label' => 'Instituții publice', 'href' => '/institutii-publice'],
                ['label' => 'Companii', 'href' => '/companii'],
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

    private static function path(): string
    {
        return '/'.ltrim(request()->path(), '/');
    }
}
