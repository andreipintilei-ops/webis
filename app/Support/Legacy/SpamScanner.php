<?php

namespace App\Support\Legacy;

/**
 * Looks for injected spam in old WordPress content before any of it is
 * imported. The old site was compromised once (the `wazambacasinoaustralia-org`
 * slug), and a hidden link to a casino is valid markup: the rich-text
 * sanitiser would keep it, so this scan is what catches it.
 *
 * Every hit is a candidate for review, not a verdict: an embedded YouTube
 * iframe is flagged exactly like a hostile one.
 */
final class SpamScanner
{
    /** Domains a link may point at without being flagged (subdomains included). */
    public const array ALLOWED_DOMAINS = [
        'webis.ro',
        // Social
        'facebook.com', 'fb.com', 'fb.me', 'instagram.com', 'linkedin.com', 'twitter.com', 'x.com',
        'youtube.com', 'youtu.be', 'youtube-nocookie.com', 'vimeo.com', 'tiktok.com', 'pinterest.com',
        'wa.me', 'whatsapp.com', 'm.me', 'messenger.com', 't.me',
        // Google and tooling
        'google.com', 'google.ro', 'goo.gl', 'g.page', 'g.co', 'gstatic.com', 'googleapis.com',
        'googletagmanager.com', 'google-analytics.com', 'wordpress.org', 'wordpress.com', 'w.org',
        'gravatar.com', 'elementor.com', 'schema.org', 'w3.org', 'github.com', 'wikipedia.org',
        // Romanian consumer-protection links every shop footer carries
        'anpc.ro', 'ec.europa.eu',
    ];

    /** Payload vocabulary of the usual injections, matched in text and in attributes alike. */
    private const string SPAM_WORDS = '/\b(casino|cazino|wazamba|pokies|plinkos?|jackpots?|maximi[sz]e your wins'
        .'|online slots?|slot machines?|free spins|sportsbook|betting|pariuri sportive'
        .'|viagra|cialis|levitra|kamagra|tramadol|xanax|online pharmacy|pharmacy online'
        .'|replica (?:watch(?:es)?|bags?|handbags?|rolex|designer)'
        .'|payday loans?|cash loans?|quick loans?|instant loans?'
        .'|crypto airdrop|airdrop|bitcoin giveaway|crypto giveaway|essay writing service)\b/iu';

    /** Ways of hiding content from visitors while search engines still read it. */
    private const array HIDDEN_CSS = [
        'display:none' => '/display\s*:\s*none/i',
        'visibility:hidden' => '/visibility\s*:\s*hidden/i',
        'text-indent' => '/text-indent\s*:\s*-\s*\d{3,}/i',
        'font-size:0' => '/font-size\s*:\s*0(?![\d.])/i',
        'height:1px' => '/(?<![\w-])height\s*:\s*[01]px/i',
        'off-screen' => '/position\s*:\s*absolute[^{}<>"]{0,200}?(?:left|top|right|bottom)\s*:\s*-\s*\d{3,}'
            .'|(?:left|top|right|bottom)\s*:\s*-\s*\d{3,}[^{}<>"]{0,200}?position\s*:\s*absolute/i',
    ];

    private const int SNIPPET_RADIUS = 60;

    /** @var list<string> */
    private readonly array $allowedDomains;

    /**
     * @param  list<string>  $extraAllowedDomains  e.g. the clients' own sites, from the projects
     */
    public function __construct(array $extraAllowedDomains = [])
    {
        $domains = array_map(self::host(...), [...self::ALLOWED_DOMAINS, ...$extraAllowedDomains]);

        $this->allowedDomains = array_values(array_unique(array_filter($domains)));
    }

    /**
     * @param  list<string>  $links  link URLs found outside the markup (Elementor's link settings)
     * @return array{hits: list<array{type: string, detail: string, snippet: string}>, domains: array<string, int>}
     */
    public function scan(string $html, array $links = []): array
    {
        $html = mb_scrub($html, 'UTF-8');
        $hits = [];

        if (preg_match_all('/<(script|iframe|object|embed)\b[^>]*>?/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $i => [$tag, $offset]) {
                $src = preg_match('#\bsrc\s*=\s*["\']?((?:https?:)?//[^"\'\s>]+)#i', $tag, $m) ? self::host($m[1]) : '';
                $hits[] = $this->hit(strtolower($matches[1][$i][0]), $src, $html, $offset);
            }
        }

        if (preg_match_all('/<[a-z][a-z0-9-]*\b[^>]*?\s(on[a-z]{3,})\s*=/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as [$attribute, $offset]) {
                $hits[] = $this->hit('event_handler', strtolower($attribute), $html, $offset);
            }
        }

        foreach (self::HIDDEN_CSS as $rule => $pattern) {
            if (preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as [, $offset]) {
                    $hits[] = $this->hit('hidden_css', $rule, $html, $offset);
                }
            }
        }

        if (preg_match_all(self::SPAM_WORDS, $html, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as [$word, $offset]) {
                $hits[] = $this->hit('spam_word', mb_strtolower($word), $html, $offset);
            }
        }

        if (preg_match_all('/base64,[A-Za-z0-9+\/=]{100,}|(?<![A-Za-z0-9+\/])[A-Za-z0-9+\/]{200,}={0,2}/', $html, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as [$blob, $offset]) {
                $hits[] = $this->hit('base64', strlen($blob).' chars', $html, $offset);
            }
        }

        if (preg_match_all('/\b(eval|atob)\s*\(/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as [$call, $offset]) {
                $hits[] = $this->hit(strtolower($call), strtolower($call).'(', $html, $offset);
            }
        }

        $domains = [];

        foreach ($this->links($html, $links) as [$url, $offset]) {
            $host = self::host($url);

            if ($host === '' || $this->isOwnSite($host)) {
                continue;
            }

            $domains[$host] = ($domains[$host] ?? 0) + 1;

            if (! $this->isAllowed($host)) {
                $hits[] = $offset === null
                    ? ['type' => 'outbound_link', 'detail' => $host, 'snippet' => $url]
                    : $this->hit('outbound_link', $host, $html, $offset);
            }
        }

        return ['hits' => $hits, 'domains' => $domains];
    }

    /**
     * Scans Elementor's JSON as the text it holds: every string value, with
     * the URLs of its link settings treated as links.
     *
     * @return array{hits: list<array{type: string, detail: string, snippet: string}>, domains: array<string, int>}
     */
    public function scanElementor(string $json): array
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            return $this->scan(stripslashes($json));
        }

        $texts = [];
        $links = [];
        $this->flatten($data, null, $texts, $links);

        return $this->scan(implode("\n", $texts), $links);
    }

    public function isAllowed(string $host): bool
    {
        $host = self::host($host);

        foreach ($this->allowedDomains as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }

    /** The lowercase host of a URL (or a bare domain), without its `www.`; '' for relative links. */
    public static function host(string $url): string
    {
        $url = trim($url);

        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        $host = preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)
            ? (string) parse_url($url, PHP_URL_HOST)
            : (preg_match('#^[a-z0-9-]+(\.[a-z0-9-]+)+$#i', $url) ? $url : '');

        return (string) preg_replace('/^www\./', '', strtolower($host));
    }

    private function isOwnSite(string $host): bool
    {
        return $host === 'webis.ro' || str_ends_with($host, '.webis.ro');
    }

    /**
     * Absolute link URLs, with their offset in the markup where they came from it.
     *
     * @param  list<string>  $extra
     * @return list<array{0: string, 1: int|null}>
     */
    private function links(string $html, array $extra): array
    {
        $links = [];

        if (preg_match_all('/<a\b[^>]*?\bhref\s*=\s*(["\']?)([^"\'\s>]+)\1/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[2] as [$url, $offset]) {
                if (preg_match('#^(https?:)?//#i', $url)) {
                    $links[] = [$url, $offset];
                }
            }
        }

        foreach ($extra as $url) {
            if (preg_match('#^(https?:)?//#i', $url)) {
                $links[] = [$url, null];
            }
        }

        return $links;
    }

    /**
     * @param  array<int|string, mixed>  $node
     * @param  list<string>  $texts
     * @param  list<string>  $links
     */
    private function flatten(array $node, int|string|null $parentKey, array &$texts, array &$links): void
    {
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $this->flatten($value, $key, $texts, $links);

                continue;
            }

            if (! is_string($value) || $value === '') {
                continue;
            }

            if ($key === 'url' && is_string($parentKey) && str_contains(strtolower($parentKey), 'link')) {
                $links[] = $value;

                continue;
            }

            $texts[] = $value;
        }
    }

    /** @return array{type: string, detail: string, snippet: string} */
    private function hit(string $type, string $detail, string $subject, int $offset): array
    {
        $start = max(0, $offset - self::SNIPPET_RADIUS);
        $snippet = substr($subject, $start, self::SNIPPET_RADIUS * 2 + 20);
        $snippet = mb_convert_encoding($snippet, 'UTF-8', 'UTF-8');

        return [
            'type' => $type,
            'detail' => $detail,
            'snippet' => trim((string) preg_replace('/\s+/u', ' ', $snippet)),
        ];
    }
}
