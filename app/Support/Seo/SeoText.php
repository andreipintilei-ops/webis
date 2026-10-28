<?php

namespace App\Support\Seo;

/**
 * Titles and descriptions held to the site's rules: a title at most 60
 * characters with its " | Webis" suffix, a description at most 155, both cut
 * at a word, never mid-word; a description in whole sentences where they fit.
 */
final class SeoText
{
    public const int TITLE_MAX = 60;

    public const int DESCRIPTION_MAX = 155;

    /** Short words a cut title or sentence may not end on. */
    private const array TRAILING = ['si', 'și', 'de', 'a', 'al', 'ale', 'cu', 'in', 'în', 'la', 'pentru', 'pe', 'sau', 'care', 'un', 'o', 'ce', 'din', 'ca', 'să', 'sa', 'the', 'and'];

    /** The plain text of HTML or a value with entities: tags out, whitespace single. */
    public static function plain(string $text): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ' ', $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * A title's own part (without the suffix), at most as long as leaves room
     * for the suffix: the old site's own suffix (" - Webis.ro") off first.
     */
    public static function title(string $title, string $suffix): string
    {
        $title = (string) preg_replace('/\s*[-|–—]\s*Webis(\.ro)?\s*$/iu', '', self::plain($title));
        $max = self::TITLE_MAX - mb_strlen($suffix);

        if (mb_strlen($title) <= $max) {
            return $title;
        }

        // Too long: ended at the last natural break that fits — after a
        // question mark or before a colon, bracket, dash or "și"/"si" —
        // while a fair share of the title is left; else at a word.
        $best = null;

        foreach ([['?', true], [':', false], [' (', false], [' – ', false], [' - ', false], [' si ', false], [' și ', false], [' & ', false]] as [$mark, $keep]) {
            $offset = 0;

            while (($at = mb_stripos($title, $mark, $offset)) !== false) {
                $end = $keep ? $at + mb_strlen($mark) : $at;

                if ($end <= $max && $end >= 20 && ($best === null || $end > $best)) {
                    $best = $end;
                }

                $offset = $at + 1;
            }
        }

        return $best !== null
            ? rtrim(mb_substr($title, 0, $best), ' ,;:–-&')
            : self::cut($title, $max, ellipsis: false);
    }

    /**
     * A description: the text's sentences, as many as fit; a first sentence
     * too long on its own, cut at a word with "…".
     */
    public static function description(string $text): string
    {
        $text = (string) preg_replace('/\s*(\.{2,}|…)\s*$/u', '.', self::plain($text));

        if (mb_strlen($text) <= self::DESCRIPTION_MAX) {
            return $text;
        }

        $kept = '';

        // A sentence ends where a capital starts the next: not inside "S.A. a".
        foreach (preg_split('/(?<=[.!?])\s+(?=[\p{Lu}„"])/u', $text) ?: [] as $sentence) {
            $next = trim($kept.' '.$sentence);

            if (mb_strlen($next) > self::DESCRIPTION_MAX) {
                break;
            }

            $kept = $next;
        }

        return $kept !== '' ? $kept : self::cut($text, self::DESCRIPTION_MAX, ellipsis: true);
    }

    /** At most `$max` characters, cut at a word, no dangling short word or punctuation. */
    private static function cut(string $text, int $max, bool $ellipsis): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $words = explode(' ', mb_substr($text, 0, ($ellipsis ? $max - 1 : $max) + 1));
        array_pop($words);

        while ($words !== [] && (in_array(mb_strtolower(trim((string) end($words), ',;:–-&')), self::TRAILING, true) || trim((string) end($words), ',;:–-&') === '')) {
            array_pop($words);
        }

        $cut = rtrim(implode(' ', $words), ' ,;:–-&');

        return $ellipsis ? $cut.'…' : $cut;
    }
}
