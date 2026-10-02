<?php

namespace App\Support;

/**
 * First path segments owned by the application. CMS pages live at root slugs
 * (/{page}), so a page may never take one of these — it would either shadow a
 * real route or be shadowed by it.
 */
final class ReservedSlugs
{
    public const array ALL = [
        // Content sections
        'blog', 'clienti', 'solutii', 'cerere-oferta', 'multumim',
        // Admin and account
        'admin', 'settings', 'login', 'logout', 'register', 'forgot-password',
        'reset-password', 'two-factor-challenge', 'user', 'email', 'passkeys',
        'confirm-password',
        // Infrastructure
        'storage', 'build', 'up', 'api', 'sitemap', 'robots', 'feed', 'rss',
        'wp-admin', 'wp-content', 'wp-includes', 'wp-login', 'xmlrpc',
        '_ui-parity', '_test',
    ];

    public static function contains(string $slug): bool
    {
        return in_array($slug, self::ALL, true) || str_starts_with($slug, 'sitemap-');
    }
}
