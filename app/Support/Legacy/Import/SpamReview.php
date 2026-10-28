<?php

namespace App\Support\Legacy\Import;

/**
 * The spam scan's flags (`spam_flags` in the URL map) and the owner's ruling
 * on them. A flagged row is refused unless it was cleared at review, so the
 * old site's November 2025 compromise cannot ride along into new pages.
 *
 * The review of 2026-10-22 cleared every flag except the two spam posts, which
 * are 410'd: a maps iframe, a Facebook Pixel, client links, the sports-betting
 * industry page's own name, a php.net link. The CSV records decisions but has
 * no column for this, so the cleared paths are listed here.
 */
final class SpamReview
{
    /** Flagged paths the owner's review marked as false alarms. */
    public const array CLEARED = [
        '/',
        '/contact',
        '/cerere-servicii-web-design-trimisa',
        '/realizare-site-profesional',
        '/realizare-site-profesional-2025',
        '/gazduire-site-wordpress',
        '/servicii/creare-site-pariuri-sportive',
        '/php-combinari-de-n-luate-cate-k',
    ];

    /**
     * Whether a row may be imported as content: unflagged, or cleared.
     *
     * @param  array<string, string>  $row
     */
    public static function allows(array $row): bool
    {
        return trim($row['spam_flags'] ?? '') === '' || in_array($row['old_path'], self::CLEARED, true);
    }
}
