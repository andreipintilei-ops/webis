<?php

namespace App\Support\RichText;

use App\Support\LinkTarget;
use App\Support\RichText\Nodes\AssetImage;
use App\Support\RichText\Nodes\Callout;
use App\Support\RichText\Nodes\Heading;
use Illuminate\Support\Str;
use Tiptap\Editor;
use Tiptap\Marks\Bold;
use Tiptap\Marks\Code;
use Tiptap\Marks\Italic;
use Tiptap\Marks\Link;
use Tiptap\Marks\Strike;
use Tiptap\Marks\Underline;
use Tiptap\Nodes\Blockquote;
use Tiptap\Nodes\BulletList;
use Tiptap\Nodes\CodeBlock;
use Tiptap\Nodes\Document;
use Tiptap\Nodes\HardBreak;
use Tiptap\Nodes\HorizontalRule;
use Tiptap\Nodes\ListItem;
use Tiptap\Nodes\OrderedList;
use Tiptap\Nodes\Paragraph;
use Tiptap\Nodes\Table;
use Tiptap\Nodes\TableCell;
use Tiptap\Nodes\TableHeader;
use Tiptap\Nodes\TableRow;
use Tiptap\Nodes\Text;

/**
 * Tiptap JSON → safe HTML, for blog bodies and rich-text blocks.
 *
 * The JSON is the source of truth; HTML is rendered once, on save. sanitize()
 * runs first and rebuilds the document from an allowlist — node types, marks,
 * attributes and link protocols — so anything the editor did not produce
 * (script, iframe, unknown nodes, javascript: links, stray attributes) never
 * reaches the page. Unknown nodes are dropped with their content rather than
 * unwrapped: tiptap-php would otherwise print their children bare.
 *
 * @phpstan-type TiptapNode array<string, mixed>
 * @phpstan-type TiptapDoc array{type: 'doc', content: list<TiptapNode>}
 * @phpstan-type TocEntry array{id: string, text: string, level: int}
 */
final class RichText
{
    public const int WORDS_PER_MINUTE = 200;

    private const array NODES = [
        'paragraph', 'heading', 'bulletList', 'orderedList', 'listItem', 'blockquote',
        'codeBlock', 'hardBreak', 'horizontalRule', 'table', 'tableRow', 'tableHeader',
        'tableCell', 'assetImage', 'callout',
    ];

    private const array MARKS = ['bold', 'italic', 'underline', 'strike', 'code', 'link'];

    private const array CALLOUT_VARIANTS = ['info', 'tip', 'warning'];

    /** Only these `rel` tokens survive from the editor; noopener is added for new tabs. */
    private const array LINK_REL_TOKENS = ['nofollow', 'sponsored', 'ugc'];

    /**
     * @return TiptapDoc
     */
    public static function emptyDocument(): array
    {
        return ['type' => 'doc', 'content' => []];
    }

    /**
     * @param  array<mixed>  $doc
     * @return TiptapDoc
     */
    public function sanitize(array $doc): array
    {
        $usedIds = [];

        return ['type' => 'doc', 'content' => $this->cleanChildren($doc, $usedIds)];
    }

    /**
     * @param  array<mixed>  $doc  a sanitized document
     */
    public function render(array $doc): string
    {
        if ($this->children($doc) === []) {
            return '';
        }

        return (new Editor(['extensions' => $this->extensions()]))
            ->setContent($doc)
            ->getHTML();
    }

    /**
     * h2 and h3 headings, in order, for the table of contents.
     *
     * @param  array<mixed>  $doc  a sanitized document
     * @return list<TocEntry>
     */
    public function toc(array $doc): array
    {
        $toc = [];

        $this->walk($doc, function (array $node) use (&$toc): void {
            $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
            $level = is_int($attrs['level'] ?? null) ? $attrs['level'] : 0;

            if (($node['type'] ?? null) === 'heading' && $level <= 3 && is_string($attrs['id'] ?? null)) {
                $toc[] = ['id' => $attrs['id'], 'text' => $this->text($node), 'level' => $level];
            }
        });

        return $toc;
    }

    /**
     * @param  array<mixed>  $doc
     */
    public function readingMinutes(array $doc): int
    {
        $words = preg_split('/\s+/u', trim($this->text($doc)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return (int) ceil(count($words) / self::WORDS_PER_MINUTE);
    }

    /**
     * Media-library images embedded in the document.
     *
     * @param  array<mixed>  $doc
     * @return list<int>
     */
    public function assetIds(array $doc): array
    {
        $ids = [];

        $this->walk($doc, function (array $node) use (&$ids): void {
            $assetId = is_array($node['attrs'] ?? null) ? ($node['attrs']['assetId'] ?? null) : null;

            if (($node['type'] ?? null) === 'assetImage' && is_int($assetId)) {
                $ids[] = $assetId;
            }
        });

        return array_values(array_unique($ids));
    }

    /**
     * Plain text, blocks separated by spaces — for word counts and excerpts.
     *
     * @param  array<mixed>  $node
     */
    public function text(array $node): string
    {
        if (($node['type'] ?? null) === 'text') {
            return is_string($node['text'] ?? null) ? $node['text'] : '';
        }

        if (($node['type'] ?? null) === 'hardBreak') {
            return ' ';
        }

        $parts = array_map(fn (array $child): string => $this->text($child), $this->children($node));
        $inline = in_array($node['type'] ?? null, ['paragraph', 'heading'], true);

        return trim(implode($inline ? '' : ' ', $parts));
    }

    /**
     * @param  array<mixed>  $node
     * @param  array<string, true>  $usedIds
     * @return list<TiptapNode>
     */
    private function cleanChildren(array $node, array &$usedIds): array
    {
        $clean = [];

        foreach ($this->children($node) as $child) {
            $child = $this->cleanNode($child, $usedIds);

            if ($child !== null) {
                $clean[] = $child;
            }
        }

        return $clean;
    }

    /**
     * @param  array<mixed>  $node
     * @param  array<string, true>  $usedIds
     * @return TiptapNode|null
     */
    private function cleanNode(array $node, array &$usedIds): ?array
    {
        $type = $node['type'] ?? null;

        if ($type === 'text') {
            $text = $node['text'] ?? null;

            if (! is_string($text) || $text === '') {
                return null;
            }

            $marks = $this->cleanMarks($node['marks'] ?? null);

            return $marks === [] ? ['type' => 'text', 'text' => $text] : ['type' => 'text', 'text' => $text, 'marks' => $marks];
        }

        if (! is_string($type) || ! in_array($type, self::NODES, true)) {
            return null;
        }

        $content = $this->cleanChildren($node, $usedIds);
        $attrs = $this->cleanAttrs($type, is_array($node['attrs'] ?? null) ? $node['attrs'] : [], $content, $usedIds);

        // A node whose required attributes are unusable (an image without an
        // asset) is dropped entirely.
        if ($attrs === null) {
            return null;
        }

        $clean = ['type' => $type];

        if ($attrs !== []) {
            $clean['attrs'] = $attrs;
        }

        if ($content !== []) {
            $clean['content'] = $content;
        }

        return $clean;
    }

    /**
     * @param  array<mixed>  $attrs
     * @param  list<TiptapNode>  $content
     * @param  array<string, true>  $usedIds
     * @return array<string, mixed>|null
     */
    private function cleanAttrs(string $type, array $attrs, array $content, array &$usedIds): ?array
    {
        $int = static fn (string $key): int => is_numeric($attrs[$key] ?? null) ? (int) $attrs[$key] : 0;

        switch ($type) {
            case 'heading':
                return [
                    'level' => max(2, min(4, $int('level'))),
                    'id' => $this->uniqueId($this->text(['type' => 'heading', 'content' => $content]), $usedIds),
                ];

            case 'orderedList':
                return $int('start') > 1 ? ['start' => $int('start')] : [];

            case 'codeBlock':
                $language = $attrs['language'] ?? null;

                return is_string($language) && preg_match('/^[a-z0-9+#-]{1,20}$/i', $language) === 1
                    ? ['language' => $language]
                    : [];

            case 'tableCell':
            case 'tableHeader':
                return array_filter([
                    'colspan' => $int('colspan') > 1 ? min(20, $int('colspan')) : null,
                    'rowspan' => $int('rowspan') > 1 ? min(20, $int('rowspan')) : null,
                ]);

            case 'assetImage':
                if ($int('assetId') < 1) {
                    return null;
                }

                $caption = is_string($attrs['caption'] ?? null) ? Str::limit(trim($attrs['caption']), 300, '') : '';

                return array_filter(['assetId' => $int('assetId'), 'caption' => $caption !== '' ? $caption : null]);

            case 'callout':
                $variant = $attrs['variant'] ?? null;

                return ['variant' => in_array($variant, self::CALLOUT_VARIANTS, true) ? $variant : 'info'];

            default:
                return [];
        }
    }

    /**
     * @return list<array{type: string, attrs?: array<string, string>}>
     */
    private function cleanMarks(mixed $marks): array
    {
        if (! is_array($marks)) {
            return [];
        }

        $clean = [];

        foreach ($marks as $mark) {
            $type = is_array($mark) ? ($mark['type'] ?? null) : null;

            if (! is_string($type) || ! in_array($type, self::MARKS, true) || isset($clean[$type])) {
                continue;
            }

            if ($type === 'link') {
                /** @var array<mixed> $mark */
                $attrs = $this->cleanLink(is_array($mark['attrs'] ?? null) ? $mark['attrs'] : []);

                // An unsafe link keeps its text; only the link goes.
                if ($attrs !== null) {
                    $clean[$type] = ['type' => 'link', 'attrs' => $attrs];
                }

                continue;
            }

            $clean[$type] = ['type' => $type];
        }

        return array_values($clean);
    }

    /**
     * Internal links render plain — no target, no rel — so they pass full link
     * equity. External links open in a new tab with noopener; the editor may
     * add nofollow, sponsored or ugc.
     *
     * @param  array<mixed>  $attrs
     * @return array<string, string>|null
     */
    private function cleanLink(array $attrs): ?array
    {
        $href = is_string($attrs['href'] ?? null) ? trim($attrs['href']) : '';

        if (! LinkTarget::isAllowed($href)) {
            return null;
        }

        if (! LinkTarget::isExternal($href)) {
            return ['href' => $href];
        }

        $requested = is_string($attrs['rel'] ?? null)
            ? preg_split('/\s+/', mb_strtolower($attrs['rel']), -1, PREG_SPLIT_NO_EMPTY) ?: []
            : [];

        $rel = ['noopener', 'noreferrer', ...array_values(array_intersect(self::LINK_REL_TOKENS, $requested))];

        return ['href' => $href, 'target' => '_blank', 'rel' => implode(' ', $rel)];
    }

    /**
     * @param  array<string, true>  $usedIds
     */
    private function uniqueId(string $text, array &$usedIds): string
    {
        $base = Str::slug(Str::limit($text, 60, '')) ?: 'sectiune';
        $id = $base;

        for ($i = 2; isset($usedIds[$id]); $i++) {
            $id = "{$base}-{$i}";
        }

        $usedIds[$id] = true;

        return $id;
    }

    /**
     * @param  array<mixed>  $node
     * @return list<array<mixed>>
     */
    private function children(array $node): array
    {
        $content = $node['content'] ?? null;

        return is_array($content) ? array_values(array_filter($content, is_array(...))) : [];
    }

    /**
     * Depth-first visit of every node below $node.
     *
     * @param  array<mixed>  $node
     * @param  callable(array<mixed>): void  $visit
     */
    private function walk(array $node, callable $visit): void
    {
        foreach ($this->children($node) as $child) {
            $visit($child);
            $this->walk($child, $visit);
        }
    }

    /**
     * @return list<object>
     */
    private function extensions(): array
    {
        return [
            new Document,
            new Paragraph,
            new Text,
            new Heading,
            new BulletList,
            new OrderedList,
            new ListItem,
            new Blockquote,
            new CodeBlock,
            new HardBreak,
            new HorizontalRule,
            new Table,
            new TableRow,
            new TableHeader,
            new TableCell,
            new AssetImage,
            new Callout,
            new Bold,
            new Italic,
            new Underline,
            new Strike,
            new Code,
            // No default target/rel: cleanLink() decides per link.
            new Link(['HTMLAttributes' => []]),
        ];
    }
}
