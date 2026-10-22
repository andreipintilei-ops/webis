<?php

namespace App\Support\Legacy\Import;

use App\Support\Seo\SeoData;

/**
 * An old item's Yoast SEO as the new site's SeoData.
 *
 * Yoast's `%%title%%`, `%%sep%%`, `%%sitename%%` and `%%page%%` are resolved as
 * WordPress rendered them; any other variable is stripped rather than shown.
 *
 * A canonical pointing at webis.ro is **never** copied: it names the old
 * address as the real one, and telling Google so is the single most
 * destructive thing this migration could do. Every discarded canonical is
 * returned to the caller for the report.
 */
final class YoastSeo
{
    /** Yoast's separator option values. */
    private const array SEPARATORS = [
        'sc-dash' => '-', 'sc-ndash' => '–', 'sc-mdash' => '—', 'sc-colon' => ':', 'sc-middot' => '·',
        'sc-bull' => '•', 'sc-star' => '*', 'sc-smstar' => '⋆', 'sc-pipe' => '|', 'sc-tilde' => '~',
        'sc-laquo' => '«', 'sc-raquo' => '»', 'sc-lt' => '<', 'sc-gt' => '>',
    ];

    /** Hosts whose canonical is the old site itself. */
    private const array OWN_HOSTS = ['webis.ro', 'www.webis.ro'];

    public function __construct(
        private readonly string $siteName = '',
        private readonly string $separator = '-',
    ) {}

    /**
     * From the `wpseo_titles` option's separator key and the blog name.
     */
    public static function fromOptions(?string $separatorOption, ?string $blogName): self
    {
        return new self(
            html_entity_decode(trim((string) $blogName), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            self::SEPARATORS[(string) $separatorOption] ?? '-',
        );
    }

    /**
     * @param  array{title?: ?string, description?: ?string, canonical?: ?string, noindex?: bool|string|int|null, focus_keyword?: ?string}  $yoast
     * @param  string  $itemTitle  what `%%title%%` stands for
     * @param  callable(string): void|null  $discarded  told about each canonical left out
     */
    public function seo(array $yoast, string $itemTitle, ?callable $discarded = null): SeoData
    {
        $canonical = trim((string) ($yoast['canonical'] ?? ''));

        if ($canonical !== '' && self::isOwnCanonical($canonical)) {
            if ($discarded !== null) {
                $discarded($canonical);
            }

            $canonical = '';
        }

        return SeoData::fromArray([
            'title' => $this->resolve((string) ($yoast['title'] ?? ''), $itemTitle),
            'description' => $this->resolve((string) ($yoast['description'] ?? ''), $itemTitle),
            'canonical' => $canonical,
            'noindex' => in_array((string) ($yoast['noindex'] ?? ''), ['1', 'true'], true),
            'focus_keyword' => trim((string) ($yoast['focus_keyword'] ?? '')),
        ]);
    }

    /**
     * A Yoast template with its variables filled in: known ones resolved,
     * the rest dropped, and the separators and spaces left dangling tidied.
     */
    public function resolve(string $template, string $itemTitle): string
    {
        if ($template === '') {
            return '';
        }

        $text = (string) preg_replace_callback('/%%([a-z_]+)%%/i', fn (array $match): string => match (strtolower($match[1])) {
            'title' => $itemTitle,
            'sitename' => $this->siteName,
            'sep' => "\u{1}",
            default => '', // %%page%% (page 1 has none), %%sitedesc%%, %%pt_plural%%…
        }, $template);

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        // A separator with nothing on one side ("Titlu - " once %%sitename%%
        // is empty) or two in a row.
        $text = (string) preg_replace('/(\x{1}\s*)+(?=\x{1})/u', '', $text);
        $text = trim($text, " \u{1}");

        return trim(str_replace("\u{1}", $this->separator, $text));
    }

    public static function isOwnCanonical(string $canonical): bool
    {
        if (str_starts_with($canonical, '/') && ! str_starts_with($canonical, '//')) {
            return true; // relative: the old site's own path
        }

        $host = mb_strtolower((string) parse_url(str_starts_with($canonical, '//') ? 'https:'.$canonical : $canonical, PHP_URL_HOST));

        return in_array($host, self::OWN_HOSTS, true);
    }
}
