<?php

namespace App\Support;

use App\Settings\FooterCtaSettings;

/**
 * The footer's closing call for a page (components/site/footer), from
 * FooterCtaSettings: the page's own wording, field by field, over the
 * default's. A page view names its key (the layout's `footer-cta-key`);
 * one that names none gets the default.
 */
final class FooterCta
{
    /**
     * Key → where it shows, for the admin. `default` first.
     *
     * @var array<string, string>
     */
    public const array PAGES = [
        'default' => 'Implicit (paginile fără una proprie)',
        'software' => 'Software la comandă',
        'products' => 'Produse SaaS',
        'web' => 'Web și e-commerce',
        'portfolio' => 'Portofoliu',
        'blog' => 'Blog (și articolele, categoriile)',
        'shop' => 'Creare magazin online',
        'presentation' => 'Creare site de prezentare',
        'project' => 'Paginile proiectelor',
    ];

    public const array FIELDS = ['title', 'intro', 'button', 'url'];

    /** When the settings give nothing at all. */
    private const array FALLBACK = [
        'title' => 'Ai un proiect în minte?',
        'intro' => 'Un site de prezentare, un magazin online sau software la comandă: spune-ne ce vrei să construiești și revenim cu pașii următori.',
        'button' => 'Hai să discutăm',
        'url' => '/contact/',
    ];

    /**
     * @return array{title: string, intro: string, button: string, url: string}
     */
    public static function for(?string $key): array
    {
        $pages = app(FooterCtaSettings::class)->pages;
        $call = self::FALLBACK;

        foreach (array_unique(['default', $key ?? 'default']) as $layer) {
            foreach (self::FIELDS as $field) {
                $value = trim((string) ($pages[$layer][$field] ?? ''));

                if ($value !== '') {
                    $call[$field] = $value;
                }
            }
        }

        $call['url'] = TrailingSlash::href($call['url']);

        return $call;
    }
}
