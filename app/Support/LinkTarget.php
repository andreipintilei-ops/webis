<?php

namespace App\Support;

/**
 * The one definition of which link targets editors may use — shared by the
 * block validation (SafeLink) and the rich-text sanitiser.
 */
final class LinkTarget
{
    /**
     * Site-relative paths, #anchors, http(s), mailto: and tel:. Everything else
     * — javascript:, data:, protocol-relative //host — is refused.
     */
    public static function isAllowed(string $href): bool
    {
        $href = trim($href);

        if ($href === '' || preg_match('/[\x00-\x20\x7F]/', $href) === 1) {
            return false;
        }

        return preg_match('~^(?:/(?!/)|#|https?://[^/]|mailto:|tel:)~i', $href) === 1;
    }

    /**
     * An absolute http(s) URL to a host other than this site (www or not).
     */
    public static function isExternal(string $href): bool
    {
        if (preg_match('~^https?://~i', $href) !== 1) {
            return false;
        }

        $host = self::bareHost((string) parse_url($href, PHP_URL_HOST));
        $ownHost = self::bareHost((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        return $host !== $ownHost && $host !== 'webis.ro';
    }

    private static function bareHost(string $host): string
    {
        $host = mb_strtolower($host);

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
