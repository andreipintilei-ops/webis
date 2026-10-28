<?php

namespace App\Support\Legacy;

use App\Support\RichText\RichText;
use App\Support\YouTube;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;

/**
 * A DOM pre-pass over WordPress HTML (classic bodies, Elementor text widgets,
 * Pods fields) that rescues content RichText::fromHtml() would otherwise eat,
 * and drops — while counting — what has no place on the new site:
 *
 * - `[shortcodes]` and `<!-- wp:… -->` comments are stripped, their content kept;
 * - h1 becomes h2 and h5/h6 become h4 (the editor allows h2–h4 only);
 * - div, section, article and friends are unwrapped, or become a paragraph
 *   when they hold only inline content, so their text is neither lost nor
 *   run together; span and font are unwrapped (a bold, italic or underline
 *   style on a span survives as the mark);
 * - each `<img>` (or `<figure>` with its `<figcaption>`) becomes an image
 *   marker, lifted to the top level, that toSegments() turns into an
 *   `assetImage` node — the node has no HTML parse rule. Images the media
 *   resolver can't place are dropped and counted;
 * - a YouTube iframe becomes a video marker where toSegments() splits the
 *   document, so the caller can put a `video` block there; other iframes,
 *   scripts, forms and embeds are dropped and counted;
 * - links to the old site are rewritten to their final target (trailing
 *   slash, no redirect hop); unresolvable ones and links to the stale
 *   staging domain keep their text but lose the link, and are counted;
 * - every attribute is dropped except a link's href and rel, a cell's
 *   colspan/rowspan and a list's start;
 * - non-breaking spaces are normalised, and empty paragraphs (`<p>&nbsp;</p>`,
 *   `<p><br></p>`) removed. Tables pass through.
 */
final class WordPressHtml
{
    /** The stale staging copy of the site that client logos still link to. */
    public const string STAGING_HOST = 'pmcwebis.wpcomstaging.com';

    public const string IMAGE_MARKER = 'WEBISLEGACYIMAGE';

    public const string VIDEO_MARKER = 'WEBISLEGACYVIDEO';

    /** Removed with their content. */
    private const array DROPPED = [
        'script', 'style', 'noscript', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea',
        'svg', 'canvas', 'video', 'audio', 'template', 'link', 'meta', 'map', 'head', 'title',
    ];

    /** Unwrapped when they hold blocks; otherwise turned into a paragraph. */
    private const array CONTAINERS = [
        'div', 'section', 'article', 'main', 'header', 'footer', 'aside', 'nav', 'center', 'address',
        'figure', 'figcaption', 'dl', 'dt', 'dd', 'details', 'summary',
    ];

    /** Inline wrappers with no meaning of their own. */
    private const array INLINE_WRAPPERS = ['span', 'font', 'small', 'big', 'mark', 'abbr', 'acronym', 'nobr', 'ins', 'cite', 'sup', 'sub', 'label', 'time'];

    private const array BLOCKS = [
        'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
        'blockquote', 'pre', 'hr', ...self::CONTAINERS,
    ];

    /** Attributes that survive, per element. */
    private const array KEPT_ATTRIBUTES = [
        'a' => ['href', 'rel'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
        'ol' => ['start'],
    ];

    public function __construct(
        private readonly MediaResolver $media,
        private readonly UrlResolver $urls,
        private readonly RichText $richText,
    ) {}

    /**
     * The HTML as documents, split where a YouTube video stood.
     *
     * @return list<array{type: 'doc', doc: array{type: 'doc', content: list<array<string, mixed>>}}|array{type: 'video', url: string}>
     */
    public function toSegments(string $html, ConversionReport $report): array
    {
        $clean = $this->clean($html, $report);
        $doc = $this->richText->fromHtml($clean['html']);

        $segments = [];
        $content = [];

        foreach ($doc['content'] as $node) {
            $marker = $this->marker($node);

            if ($marker !== null && $marker[0] === self::VIDEO_MARKER) {
                $this->appendDoc($segments, $content);
                $segments[] = ['type' => 'video', 'url' => $clean['videos'][$marker[1]]];
                $content = [];

                continue;
            }

            if ($marker !== null) {
                $image = $clean['images'][$marker[1]];
                $node = ['type' => 'assetImage', 'attrs' => array_filter(['assetId' => $image['assetId'], 'caption' => $image['caption']])];
            }

            $content[] = $node;
        }

        $this->appendDoc($segments, $content);

        return $segments;
    }

    /**
     * Appends the nodes as a sanitized document, unless nothing is left.
     *
     * @param  list<array{type: 'doc', doc: array{type: 'doc', content: list<array<string, mixed>>}}|array{type: 'video', url: string}>  $segments
     * @param  list<array<mixed>>  $content
     */
    private function appendDoc(array &$segments, array $content): void
    {
        $doc = $this->richText->sanitize(['type' => 'doc', 'content' => $content]);

        if ($doc['content'] !== []) {
            $segments[] = ['type' => 'doc', 'doc' => $doc];
        }
    }

    /**
     * The cleaned HTML, with image and video markers, and what each marker
     * stands for.
     *
     * @return array{html: string, images: list<array{assetId: int, caption: string|null}>, videos: list<string>}
     */
    public function clean(string $html, ConversionReport $report): array
    {
        $html = $this->stripShortcodes($html, $report);
        $html = $this->autop($html);

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><html><body><div>'.$html.'</div></body></html>', LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $root = $this->nodes($xpath, '/html/body/div')[0] ?? null;

        if (! $root instanceof DOMElement) {
            return ['html' => '', 'images' => [], 'videos' => []];
        }

        $images = [];
        $videos = [];

        foreach ($this->nodes($xpath, './/comment()', $root) as $comment) {
            if (str_starts_with(trim((string) $comment->nodeValue), 'wp:') || str_starts_with(trim((string) $comment->nodeValue), '/wp:')) {
                $report->drop('wp-block-comment');
            }

            $comment->parentNode?->removeChild($comment);
        }

        foreach (self::DROPPED as $tag) {
            foreach ($this->elements($root, $tag) as $element) {
                $report->drop("element:{$tag}");
                $element->parentNode?->removeChild($element);
            }
        }

        foreach ($this->elements($root, 'iframe') as $iframe) {
            $url = $this->youtubeUrl($iframe->getAttribute('src') ?: $iframe->getAttribute('data-src'));

            if ($url === null) {
                $report->drop('element:iframe');
                $iframe->parentNode?->removeChild($iframe);

                continue;
            }

            $videos[] = $url;
            $this->placeMarker($root, $iframe, self::VIDEO_MARKER.(count($videos) - 1));
        }

        foreach ($this->elements($root, 'figure') as $figure) {
            $img = $figure->getElementsByTagName('img')->item(0);

            if ($img === null) {
                continue;
            }

            $caption = $figure->getElementsByTagName('figcaption')->item(0);
            $this->placeImage($root, $figure, $img, $caption === null ? null : self::inlineText($this->innerHtml($caption)), $images, $report);
        }

        foreach ($this->elements($root, 'img') as $img) {
            $this->placeImage($root, $img, $img, null, $images, $report);
        }

        $this->styledSpansToMarks($dom, $root);

        foreach (self::INLINE_WRAPPERS as $tag) {
            foreach ($this->elements($root, $tag) as $element) {
                $this->unwrap($element);
            }
        }

        foreach (['h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4'] as $from => $to) {
            foreach ($this->elements($root, $from) as $heading) {
                $this->rename($heading, $to);
            }
        }

        foreach ($this->elements($root, 'a') as $link) {
            $this->rewriteLinkElement($link, $report);
        }

        // Deepest first, so a container sees its children already settled.
        $containers = [];

        foreach ($this->nodes($xpath, './/*', $root) as $element) {
            if ($element instanceof DOMElement && in_array($element->nodeName, self::CONTAINERS, true)) {
                $containers[] = $element;
            }
        }

        foreach (array_reverse($containers) as $container) {
            $this->settleContainer($container);
        }

        foreach ($this->nodes($xpath, './/*', $root) as $element) {
            if ($element instanceof DOMElement) {
                $this->stripAttributes($element);
            }
        }

        $root->normalize();
        $this->normaliseWhitespace($root);
        $this->removeEmpty($root, $xpath);

        // The editor's schema wants paragraphs at the top level and inside
        // list items, cells and quotes.
        foreach ($this->nodes($xpath, './/li|.//td|.//th|.//blockquote', $root) as $holder) {
            if ($holder instanceof DOMElement) {
                $this->wrapInlineRuns($holder);
            }
        }

        $this->wrapInlineRuns($root);

        $html = '';

        foreach (iterator_to_array($root->childNodes) as $child) {
            $html .= $dom->saveHTML($child);
        }

        return ['html' => $html, 'images' => $images, 'videos' => $videos];
    }

    /**
     * Where a link should point on the new site, or null when it must be
     * unlinked (its text stays): internal links resolve to their final
     * target, `tel:`/`mailto:` lose stray spaces, external links pass, and
     * links to the staging domain, unresolvable internal pages and unsafe
     * schemes go — each counted.
     */
    public function rewriteLink(string $href, ConversionReport $report): ?string
    {
        $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($href === '' || $href === '#') {
            return null;
        }

        if (str_starts_with($href, '#')) {
            return $href;
        }

        if (preg_match('~^(tel|mailto):~i', $href) === 1) {
            return (string) preg_replace('/\s+/', '', $href);
        }

        $host = mb_strtolower((string) parse_url($href, PHP_URL_HOST));

        if ($host === self::STAGING_HOST) {
            $report->drop('link:staging');

            return null;
        }

        if (LegacyUrlIndex::isInternal($href)) {
            if (LegacyMediaIndex::uploadsFile($href) !== null) {
                // A file: it follows its asset once the media import adds the redirects.
                $report->unresolved('link:media', $href);

                return str_starts_with($href, '/') ? UrlMap::SITE_URL.$href : $href;
            }

            $target = $this->urls->resolve($href);

            if ($target === null) {
                $report->unresolved('link', $href);
            }

            return $target;
        }

        if (preg_match('~^https?://[^/]~i', $href) === 1) {
            return $href;
        }

        $report->drop('link:unsafe');

        return null;
    }

    /**
     * An image's asset: by its attachment id when known, else by URL.
     */
    public function resolveImage(?int $attachmentId, string $url): ?int
    {
        $assetId = $attachmentId !== null && $attachmentId > 0 ? $this->media->assetIdForAttachment($attachmentId) : null;

        return $assetId ?? ($url !== '' ? $this->media->assetIdForUrl($url) : null);
    }

    /**
     * Plain text of an HTML fragment: one line per block, inline markup and
     * shortcodes gone, entities decoded, spaces collapsed.
     */
    public static function plainText(string $html): string
    {
        $html = (string) preg_replace('~<(script|style|noscript|template)\b.*?</\1>~is', '', $html);
        $html = (string) preg_replace('/\[\/?[a-z][a-z0-9_-]*(?:\s[^\]]*)?\]/i', '', $html);
        $html = (string) preg_replace('~<li\b[^>]*>~i', "\n– ", $html);
        $html = (string) preg_replace('~<br\s*/?>|</(p|div|li|h[1-6]|tr|blockquote|section|ul|ol)>~i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{a0}", ' ', $text);

        $lines = array_map(fn (string $line): string => trim((string) preg_replace('/[ \t]+/u', ' ', $line)), explode("\n", $text));

        return implode("\n", array_values(array_filter($lines, fn (string $line): bool => $line !== '' && $line !== '–')));
    }

    /**
     * Plain text on one line, for headings, titles and labels.
     */
    public static function inlineText(string $html): string
    {
        $html = (string) preg_replace('~<br\s*/?>~i', ' ', $html);

        return trim((string) preg_replace('/\s+/u', ' ', str_replace("\n", ' ', self::plainText($html))));
    }

    /**
     * The marker a top-level node stands for: [marker, index], or null.
     *
     * @param  array<mixed>  $node
     * @return array{0: string, 1: int}|null
     */
    private function marker(array $node): ?array
    {
        $content = $node['content'] ?? null;

        if (($node['type'] ?? null) !== 'paragraph' || ! is_array($content) || count($content) !== 1) {
            return null;
        }

        $text = is_array($content[0]) ? ($content[0]['text'] ?? null) : null;

        if (is_string($text) && preg_match('/^('.self::IMAGE_MARKER.'|'.self::VIDEO_MARKER.')(\d+)$/', $text, $match) === 1) {
            return [$match[1], (int) $match[2]];
        }

        return null;
    }

    private function stripShortcodes(string $html, ConversionReport $report): string
    {
        return (string) preg_replace_callback('/\[\/?[a-z][a-z0-9_-]*(?:\s[^\]]*)?\/?\]/i', function (array $match) use ($report): string {
            if (! str_starts_with($match[0], '[/')) {
                $report->drop('shortcode');
            }

            return '';
        }, $html);
    }

    /**
     * WordPress's classic editor stores paragraphs as blank lines (wpautop
     * adds the tags on display). Content with no block markup at all gets
     * them here.
     */
    private function autop(string $html): string
    {
        if (preg_match('~<(p|div|h[1-6]|ul|ol|table|blockquote|pre|figure|section)\b~i', $html) === 1) {
            return $html;
        }

        $paragraphs = preg_split('/\n\s*\n/', str_replace(["\r\n", "\r"], "\n", trim($html))) ?: [];

        return implode('', array_map(
            fn (string $p): string => '<p>'.preg_replace('/\s*\n\s*/', '<br>', trim($p)).'</p>',
            array_filter($paragraphs, fn (string $p): bool => trim($p) !== ''),
        ));
    }

    /**
     * @param  list<array{assetId: int, caption: string|null}>  $images
     */
    private function placeImage(DOMElement $root, DOMElement $replaced, DOMElement $img, ?string $caption, array &$images, ConversionReport $report): void
    {
        if ($replaced->parentNode === null) {
            return;
        }

        $src = $img->getAttribute('src') ?: ($img->getAttribute('data-src') ?: $img->getAttribute('data-lazy-src'));
        $attachmentId = preg_match('/\bwp-image-(\d+)\b/', $img->getAttribute('class'), $m) === 1
            ? (int) $m[1]
            : ((int) $img->getAttribute('data-attachment-id') ?: null);

        $assetId = $this->resolveImage($attachmentId, $src);

        if ($assetId === null) {
            $report->unresolved('image', $src !== '' ? $src : "attachment #{$attachmentId}");
            $replaced->parentNode->removeChild($replaced);

            return;
        }

        $images[] = ['assetId' => $assetId, 'caption' => $caption !== null && $caption !== '' ? $caption : null];
        $this->placeMarker($root, $replaced, self::IMAGE_MARKER.(count($images) - 1));
    }

    /**
     * Replaces $element with a marker paragraph at the top level: in its
     * place when it is a top-level node, else right after its top-level
     * ancestor (after earlier markers lifted out of the same ancestor).
     */
    private function placeMarker(DOMElement $root, DOMElement $element, string $text): void
    {
        $marker = $root->ownerDocument->createElement('p');
        $marker->appendChild($root->ownerDocument->createTextNode($text));

        if ($element->parentNode === $root) {
            $root->replaceChild($marker, $element);

            return;
        }

        $ancestor = $element;

        while ($ancestor->parentNode !== null && $ancestor->parentNode !== $root) {
            $ancestor = $ancestor->parentNode;
        }

        $element->parentNode?->removeChild($element);

        $after = $ancestor;

        while ($after->nextSibling instanceof DOMElement && $after->nextSibling->getAttribute('data-legacy-marker') === '1') {
            $after = $after->nextSibling;
        }

        $marker->setAttribute('data-legacy-marker', '1');
        $root->insertBefore($marker, $after->nextSibling);
    }

    private function youtubeUrl(string $src): ?string
    {
        $src = trim($src);

        if (str_starts_with($src, '//')) {
            $src = "https:{$src}";
        }

        $id = stripos($src, 'youtu') !== false ? YouTube::idFromUrl($src) : null;

        return $id === null ? null : "https://www.youtube.com/watch?v={$id}";
    }

    /**
     * A span styled bold, italic or underlined keeps that as a real mark.
     */
    private function styledSpansToMarks(DOMDocument $dom, DOMElement $root): void
    {
        foreach ($this->elements($root, 'span') as $span) {
            $style = mb_strtolower(str_replace(' ', '', $span->getAttribute('style')));
            $tags = array_keys(array_filter([
                'strong' => preg_match('/font-weight:(bold|[6-9]00)/', $style) === 1,
                'em' => str_contains($style, 'font-style:italic'),
                'u' => str_contains($style, 'text-decoration:underline'),
            ]));

            foreach ($tags as $tag) {
                $mark = $dom->createElement($tag);

                while ($span->firstChild !== null) {
                    $mark->appendChild($span->firstChild);
                }

                $span->appendChild($mark);
            }
        }
    }

    private function rewriteLinkElement(DOMElement $link, ConversionReport $report): void
    {
        // A link wrapping nothing but an image (a lightbox) went with the image.
        if (trim($link->textContent) === '' && $link->getElementsByTagName('img')->length === 0) {
            $link->parentNode?->removeChild($link);

            return;
        }

        $href = $this->rewriteLink($link->getAttribute('href'), $report);

        if ($href === null) {
            $this->unwrap($link);

            return;
        }

        $link->setAttribute('href', $href);
    }

    private function settleContainer(DOMElement $container): void
    {
        if ($container->parentNode === null) {
            return;
        }

        foreach ($container->childNodes as $child) {
            if ($child instanceof DOMElement && in_array($child->nodeName, self::BLOCKS, true)) {
                $this->unwrap($container);

                return;
            }
        }

        if (trim(str_replace("\u{a0}", ' ', $container->textContent)) === '' && $container->getElementsByTagName('br')->length === 0) {
            $container->parentNode->removeChild($container);

            return;
        }

        $this->rename($container, 'p');
    }

    private function stripAttributes(DOMElement $element): void
    {
        $kept = self::KEPT_ATTRIBUTES[$element->nodeName] ?? [];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array($attribute->nodeName, $kept, true)) {
                $element->removeAttribute($attribute->nodeName);
            }
        }
    }

    /**
     * Non-breaking spaces become plain ones and runs of whitespace collapse
     * (outside `<pre>`); whitespace-only text between blocks goes.
     */
    private function normaliseWhitespace(DOMElement $root): void
    {
        $walk = function (DOMNode $node, bool $pre) use (&$walk): void {
            foreach (iterator_to_array($node->childNodes) as $child) {
                if ($child instanceof DOMText) {
                    if (! $pre) {
                        $child->nodeValue = (string) preg_replace('/[\s\x{a0}]+/u', ' ', (string) $child->nodeValue);
                    }

                    continue;
                }

                $walk($child, $pre || $child->nodeName === 'pre');
            }
        };

        $walk($root, false);

        $structural = ['div', 'ul', 'ol', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'blockquote'];
        $trimmed = ['p', 'h2', 'h3', 'h4', 'li', 'td', 'th'];
        $xpath = new DOMXPath($root->ownerDocument);

        foreach ($this->nodes($xpath, './/text()', $root) as $text) {
            $parent = $text->parentNode;

            if ($parent !== null && in_array($parent->nodeName, $structural, true) && trim((string) $text->nodeValue) === '') {
                $parent->removeChild($text);
            }
        }

        foreach ($this->nodes($xpath, './/*', $root) as $block) {
            if (! $block instanceof DOMElement || ! in_array($block->nodeName, $trimmed, true)) {
                continue;
            }

            $texts = $this->nodes($xpath, './/text()', $block);

            if ($texts !== []) {
                $texts[0]->nodeValue = ltrim((string) $texts[0]->nodeValue);
                $last = $texts[count($texts) - 1];
                $last->nodeValue = rtrim((string) $last->nodeValue);
            }
        }
    }

    /**
     * Removes paragraphs and headings with no text (`<p>&nbsp;</p>`,
     * `<p><br></p>`), line breaks at either end of a paragraph, and inline
     * elements left empty.
     */
    private function removeEmpty(DOMElement $root, DOMXPath $xpath): void
    {
        foreach ($this->nodes($xpath, './/p|.//h2|.//h3|.//h4|.//li', $root) as $block) {
            if (! $block instanceof DOMElement) {
                continue;
            }

            foreach (['firstChild', 'lastChild'] as $end) {
                while ($block->{$end} instanceof DOMElement && $block->{$end}->nodeName === 'br') {
                    $block->removeChild($block->{$end});
                }
            }

            if (trim($block->textContent) === '' && $block->nodeName !== 'li') {
                $block->parentNode?->removeChild($block);
            }
        }

        foreach ($this->nodes($xpath, './/strong|.//b|.//em|.//i|.//u|.//s|.//a', $root) as $inline) {
            if ($inline instanceof DOMElement && $inline->textContent === '') {
                $inline->parentNode?->removeChild($inline);
            }
        }

        foreach ($this->nodes($xpath, './/ul|.//ol', $root) as $list) {
            if ($list instanceof DOMElement && trim($list->textContent) === '') {
                $list->parentNode?->removeChild($list);
            }
        }
    }

    /**
     * Text and inline elements left directly in $parent (an unwrapped div's
     * text, a bare classic body, a list item's text) are wrapped in
     * paragraphs.
     */
    private function wrapInlineRuns(DOMElement $parent): void
    {
        $run = [];

        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child instanceof DOMElement && in_array($child->nodeName, self::BLOCKS, true)) {
                $this->wrapRun($parent, $run);
                $run = [];

                continue;
            }

            $run[] = $child;
        }

        $this->wrapRun($parent, $run);
    }

    /**
     * @param  list<DOMNode>  $run  consecutive inline children of $parent
     */
    private function wrapRun(DOMElement $parent, array $run): void
    {
        if ($run === []) {
            return;
        }

        if (trim(implode('', array_map(fn (DOMNode $node): string => (string) $node->textContent, $run))) === '') {
            foreach ($run as $node) {
                $parent->removeChild($node);
            }

            return;
        }

        $p = $parent->ownerDocument->createElement('p');
        $parent->insertBefore($p, $run[0]);

        foreach ($run as $node) {
            $p->appendChild($node);
        }
    }

    /**
     * @return list<DOMElement>
     */
    private function elements(DOMElement $root, string $tag): array
    {
        return array_values(iterator_to_array($root->getElementsByTagName($tag)));
    }

    /**
     * The nodes an XPath query finds, as a list.
     *
     * @return list<DOMNode>
     */
    private function nodes(DOMXPath $xpath, string $query, ?DOMNode $context = null): array
    {
        $found = $xpath->query($query, $context);

        return $found === false ? [] : array_values(array_filter(iterator_to_array($found), fn (mixed $node): bool => $node instanceof DOMNode));
    }

    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private function rename(DOMElement $element, string $tag): DOMElement
    {
        $renamed = $element->ownerDocument->createElement($tag);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $renamed->setAttribute($attribute->nodeName, (string) $attribute->nodeValue);
        }

        while ($element->firstChild !== null) {
            $renamed->appendChild($element->firstChild);
        }

        $element->parentNode?->replaceChild($renamed, $element);

        return $renamed;
    }

    private function innerHtml(DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }

        return $html;
    }
}
