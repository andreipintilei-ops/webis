<?php

namespace App\Support\Legacy;

use App\Blocks\BlockRegistry;
use Illuminate\Support\Str;

/**
 * Rebuilds an Elementor page (`_elementor_data`: sections, columns and
 * containers around widgets) as an ordered list of native blocks. Content
 * survives, Elementor's layout does not: the blocks take the new site's
 * design. Every widget that is not carried over is counted in the report.
 *
 * How the tree is read (worked out on the real pages and articles):
 *
 * - **Items.** The tree is flattened in document order into content items,
 *   each remembering its top-level section and parent element. Text-editor
 *   widgets holding one short paragraph set in a heavy or large type
 *   (weight ≥ 600 or ≥ 30px — how the 2022 articles and the service pages
 *   draw their headings) count as headings. A heading with `header_size`
 *   p/div/span, or linking to tel:/mailto:, is a paragraph. An animated
 *   headline is a heading: its before, rotating (or highlighted) and after
 *   text.
 * - **Hero.** Articles' h1 was the post title, printed by the theme's
 *   single-post template, so their hero is the title and nothing is
 *   absorbed. Elsewhere the hero is the first `h1` heading, else the first
 *   heading in the opening two sections, else the title. From the hero's
 *   section (an opening image + text layout counts as one) it takes: the
 *   large heading lines right after it (a one-word or lower-case line
 *   continues the title, any other becomes the subheading), the first short
 *   text after it as the subheading (≤ 400 characters), up to two buttons
 *   before the next heading, and the first content image.
 * - **Later headings** become the heading of the block that follows them
 *   (features, FAQ, stats, prices, gallery, image + text, call to action,
 *   form; a video's title) — even from the section before, since the 2022
 *   articles give every widget a section of its own — otherwise an h2–h4 in
 *   the rich text. A heading right above a listing that is dropped
 *   (testimonials, post grids, maps) is dropped with it. Levels are
 *   ranked per page: the largest size used becomes h2, the next h3, the rest
 *   h4 — an article drawn entirely in h5 gets h2s. A short heading followed
 *   by a short text right before features, prices or a call to action
 *   becomes that block's heading and intro.
 * - **Eyebrows** — a small (≤ 20px) upper-case heading directly above
 *   another in the same container ("Serviciile noastre" over "Creare magazin
 *   online") — are dropped: no block but the hero has a slot for one.
 * - **Rich text.** Consecutive text, headings, inline images and plain
 *   icon lists merge into one `rich_text` block, cleaned by WordPressHtml.
 * - **Image + text.** A container of exactly two columns, one holding a
 *   single content image and the other only headings and text, becomes
 *   `image_text` (the image on the side it stood); when its image can't be
 *   resolved, the text just continues the rich text. Any other image is an
 *   inline image in the rich text.
 * - **Cards.** `image-box` and `icon-box`, and hand-built cards (a container
 *   of an image and a heading, plus optionally a text), become `features`
 *   items — consecutive ones in the same section form one block; the
 *   container's link becomes the card's link.
 * - **Lists.** An `icon-list` linking to #anchors (or right under a "Ce poți
 *   învăța…" heading) is the old table of contents: dropped with its heading,
 *   since the new article template builds its own. Any other icon list is a
 *   bullet list in the rich text, not `features`: in the articles they are
 *   running prose.
 * - **Toggle/accordion** → `faq` (answers as plain text; a group with an
 *   answer over 2,000 characters stays rich text instead). The FAQ
 *   section's own heading and line above the questions become the faq's
 *   intro, its illustration dropped (foldFaqSections). `price-table` →
 *   `pricing_tiers`, `counter` and `progress` (the bar's share as the
 *   figure, "78%") → `stats`, `gallery`/`image-carousel` →
 *   `gallery`, a YouTube `video` → `video` (hosted MP4s are counted).
 * - **Buttons** under a heading (and optionally a short text) in the same
 *   section become a `cta_banner`, a second one its secondary button; a
 *   button after an image + text block becomes that block's link; any other
 *   is counted. A button opening the quote popup links to /contact/.
 * - **Forms.** A section holding a form is gathered as one `quote_form` (its
 *   first heading the form's heading, the next short text its intro; the
 *   rest of the section, contact details and hashtags, counted) — then
 *   dropped and counted with it: the footer's call to talk ends every page.
 * - **Responsive copies.** An element hidden on desktop is the phone or
 *   tablet duplicate of one the desktop shows; it is dropped and counted.
 * - **Dropped and counted:** spacers, dividers, maps, social icons, hotspots,
 *   testimonial carousels, loop grids, post lists, share
 *   buttons, theme widgets, shortcodes, hosted (non-YouTube) videos, and
 *   decorative images (an opacity, or an absolute position without a
 *   caption: the clouds and planets). Links to the stale staging domain
 *   inside anything dropped whole are counted too.
 *
 * @phpstan-type Item array<string, mixed>
 */
final class ElementorToBlocks
{
    /** Dynamic lists and embeds: dropped together with the headings right above them. */
    private const array LISTINGS = [
        'testimonial-carousel', 'loop-grid', 'loop-carousel', 'posts', 'portfolio', 'google_maps', 'social-icons',
        'share-buttons', 'reviews',
    ];

    private const string TOC_HEADING = '/\b(inv[aă]ț?t?a|cuprins|con[tț]inut)\b/iu';

    private const string POPUP_TARGET = '/contact/';

    private ConversionReport $report;

    /** @var array<int, int> Elementor heading rank → rich-text level */
    private array $levels = [];

    // While assembling: the page title, the blocks so far, the items waiting
    // to become rich text, and the run of cards (or questions …) being gathered.

    private string $title = '';

    /** @var list<array<string, mixed>> */
    private array $blocks = [];

    /** @var list<Item> */
    private array $buffer = [];

    /** @var array<string, mixed>|null */
    private ?array $group = null;

    public function __construct(
        private readonly WordPressHtml $html,
        private readonly BlockListValidator $validator,
        private readonly BlockRegistry $registry,
    ) {}

    /**
     * @param  array<mixed>|string  $data  `_elementor_data`, decoded or not
     * @param  bool  $titleIsHeading  whether the theme printed the title as the page's h1 (single posts)
     */
    public function convert(array|string $data, string $title, bool $titleIsHeading = false): Conversion
    {
        $this->report = new ConversionReport;
        $elements = is_string($data) ? json_decode($data, true) : $data;

        if (! is_array($elements)) {
            $this->report->note('The Elementor data is not valid JSON.');

            return new Conversion([], $this->report);
        }

        $items = [];

        foreach (array_values($elements) as $section => $element) {
            if (is_array($element)) {
                $this->collect($element, $section, '', null, $items);
            }
        }

        $items = $this->dropTablesOfContents($items);
        $items = $this->dropEyebrows($items);
        $items = $this->collapseFormSections($items);
        [$hero, $items] = $this->hero($items, $title, $titleIsHeading);
        $this->levels = $this->headingLevels($items);

        $blocks = [$this->block('hero', $hero), ...$this->assemble($items, $title)];

        // The old pages' forms, at the end or further up (the free SEO
        // audit's): the footer's own call to talk ends every page instead.
        $forms = array_filter($blocks, fn (array $block): bool => $block['type'] === 'quote_form');
        $blocks = array_values(array_diff_key($blocks, $forms));
        $this->report->drop('form (the footer\'s call instead)', count($forms));

        $blocks = $this->foldFaqSections($blocks);

        if (! $titleIsHeading) {
            $blocks = $this->foldIntoHero($blocks);
        }

        return new Conversion($this->validator->validate($blocks, $this->report), $this->report);
    }

    // ---- Reading the tree ---------------------------------------------------

    /**
     * @param  array<mixed>  $element
     * @param  list<Item>  $items
     */
    private function collect(array $element, int $section, string $parent, ?string $link, array &$items): void
    {
        $type = (string) ($element['elType'] ?? '');
        $settings = is_array($element['settings'] ?? null) ? $element['settings'] : [];
        $children = array_values(array_filter((array) ($element['elements'] ?? []), is_array(...)));
        $id = (string) ($element['id'] ?? '');

        // The phone and tablet copy of something the desktop shows too.
        if (($settings['hide_desktop'] ?? null) === 'hidden-desktop') {
            $this->report->drop('hidden on desktop: '.($type === 'widget' ? (string) ($element['widgetType'] ?? '') : $type));
            $this->countStagingLinks($element);

            return;
        }

        if ($type === 'widget') {
            $produced = $this->widget($element, $settings, $link);

            foreach ($produced as $item) {
                $items[] = ['section' => $section, 'parent' => $parent, ...$item];
            }

            // A heading over a listing that is not carried over would head nothing.
            if ($produced === [] && in_array($element['widgetType'] ?? null, self::LISTINGS, true)) {
                while (($last = end($items)) !== false && $last['kind'] === 'heading' && $last['section'] === $section) {
                    array_pop($items);
                    $this->report->drop('heading (of a dropped listing)');
                }
            }

            return;
        }

        $link = $this->settingUrl($settings['link'] ?? null) ?? $link;

        if (($imageText = $this->imageText($children, $link)) !== null) {
            $items[] = ['section' => $section, 'parent' => $id, ...$imageText];

            return;
        }

        if (($card = $this->card($type, $children, $link)) !== null) {
            $items[] = ['section' => $section, 'parent' => $id, ...$card];

            return;
        }

        foreach ($children as $child) {
            $this->collect($child, $section, $id, $link, $items);
        }
    }

    /**
     * @param  array<mixed>  $element
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function widget(array $element, array $settings, ?string $containerLink): array
    {
        $type = (string) ($element['widgetType'] ?? '');

        return match ($type) {
            'heading' => $this->headingWidget($settings),
            'animated-headline' => $this->animatedHeadline($settings),
            'text-editor' => $this->textEditor($settings),
            'html' => [['kind' => 'html', 'html' => (string) ($settings['html'] ?? '')]],
            'image' => $this->image($settings),
            'image-box', 'icon-box' => $this->feature($settings, $containerLink),
            'icon-list' => $this->iconList($settings),
            'toggle', 'accordion' => $this->faq($settings),
            'price-table' => [$this->tier($settings)],
            'counter' => [$this->stat($settings)],
            'progress' => $this->share($settings),
            'gallery', 'image-carousel' => $this->gallery($settings),
            'video' => $this->video($settings),
            'button' => $this->button($settings),
            'form' => [['kind' => 'form']],
            default => $this->skip($type, $settings),
        };
    }

    /**
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function headingWidget(array $settings): array
    {
        $text = WordPressHtml::inlineText((string) ($settings['title'] ?? ''));
        $size = (string) ($settings['header_size'] ?? 'h2');
        $link = $this->settingUrl($settings['link'] ?? null);

        if ($text === '') {
            $this->report->drop('widget:heading (empty)');

            return [];
        }

        // Elementor's default title, left in by mistake.
        if (in_array($text, ['Adaugă aici textul pentru subtitlu', 'Add Your Heading Text Here'], true)) {
            $this->report->drop('widget:heading (placeholder)');

            return [];
        }

        if (! preg_match('/^h[1-6]$/', $size) || ($link !== null && preg_match('~^(tel|mailto):~i', $link) === 1)) {
            return [['kind' => 'html', 'html' => '<p>'.$this->linked(e($text), $link).'</p>']];
        }

        return [[
            'kind' => 'heading',
            'text' => $text,
            'rank' => (int) substr($size, 1),
            'size' => $this->fontSize($settings),
            'eyebrow' => ($settings['typography_text_transform'] ?? null) === 'uppercase' && $this->fontSize($settings) > 0 && $this->fontSize($settings) <= 20,
            'link' => $link,
        ]];
    }

    /**
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function animatedHeadline(array $settings): array
    {
        $middle = ($settings['headline_style'] ?? 'highlight') === 'rotate'
            ? implode(', ', array_filter(array_map('trim', explode("\n", (string) ($settings['rotating_text'] ?? '')))))
            : (string) ($settings['highlighted_text'] ?? '');

        $text = WordPressHtml::inlineText(implode(' ', [(string) ($settings['before_text'] ?? ''), $middle, (string) ($settings['after_text'] ?? '')]));
        $tag = (string) ($settings['tag'] ?? 'h3');

        return $text === '' ? [] : [[
            'kind' => 'heading',
            'text' => $text,
            'rank' => preg_match('/^h([1-6])$/', $tag, $m) === 1 ? (int) $m[1] : 2,
            'size' => $this->fontSize($settings),
            'eyebrow' => false,
            'link' => null,
        ]];
    }

    /**
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function textEditor(array $settings): array
    {
        $html = (string) ($settings['editor'] ?? '');
        $text = WordPressHtml::inlineText($html);

        if ($text === '') {
            return [];
        }

        $weight = (int) ($settings['typography_font_weight'] ?? 0);
        $size = $this->fontSize($settings);
        $blocks = preg_match_all('~<(p|div|h[1-6]|li|br|table)\b~i', $html);

        if (mb_strlen($text) <= 120 && $blocks <= 1 && ($weight >= 600 || $size >= 30)) {
            return [['kind' => 'heading', 'text' => $text, 'rank' => 2, 'size' => $size, 'eyebrow' => false, 'link' => null, 'styled' => true]];
        }

        return [['kind' => 'text', 'html' => $html]];
    }

    /**
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function image(array $settings): array
    {
        if ($this->isDecorative($settings)) {
            $this->report->drop('widget:image (decorative)');

            return [];
        }

        $image = is_array($settings['image'] ?? null) ? $settings['image'] : [];
        $url = (string) ($image['url'] ?? '');

        if ($url === '' && empty($image['id'])) {
            $this->report->drop('widget:image (empty)');

            return [];
        }

        $caption = ($settings['caption_source'] ?? '') === 'custom' || isset($settings['caption'])
            ? WordPressHtml::inlineText((string) ($settings['caption'] ?? ''))
            : '';

        return [['kind' => 'image', 'id' => (int) ($image['id'] ?? 0), 'url' => $url, 'caption' => $caption !== '' ? $caption : null]];
    }

    /**
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function feature(array $settings, ?string $containerLink): array
    {
        $title = WordPressHtml::inlineText((string) ($settings['title_text'] ?? ''));
        $text = WordPressHtml::inlineText((string) ($settings['description_text'] ?? ''));
        $link = $this->settingUrl($settings['link'] ?? null) ?? $containerLink;

        if ($title === '' && $text === '') {
            $this->report->drop('widget:image-box (empty)');

            return [];
        }

        // Contact details drawn as cards: a paragraph with the link.
        if ($link !== null && $this->isContactLink($link)) {
            return [['kind' => 'html', 'html' => '<p>'.$this->linked(e($title !== '' ? $title : $text), $link).'</p>', 'contact' => true]];
        }

        return [['kind' => 'feature', 'title' => $title, 'text' => $text, 'link' => $link]];
    }

    /**
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function iconList(array $settings): array
    {
        $entries = array_values(array_filter((array) ($settings['icon_list'] ?? []), is_array(...)));
        $anchors = array_filter($entries, fn (array $entry): bool => str_contains((string) $this->settingUrl($entry['link'] ?? null), '#'));

        if ($entries !== [] && count($anchors) * 2 >= count($entries)) {
            return [['kind' => 'toc']];
        }

        $lis = '';

        foreach ($entries as $entry) {
            $text = WordPressHtml::inlineText((string) ($entry['text'] ?? ''));

            if ($text !== '') {
                $lis .= '<li>'.$this->linked(e($text), $this->settingUrl($entry['link'] ?? null)).'</li>';
            }
        }

        return $lis === '' ? [] : [['kind' => 'html', 'html' => "<ul>{$lis}</ul>", 'list' => true]];
    }

    /**
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function faq(array $settings): array
    {
        $items = [];

        foreach ((array) ($settings['tabs'] ?? []) as $tab) {
            $question = WordPressHtml::inlineText((string) ($tab['tab_title'] ?? ''));
            $answer = (string) ($tab['tab_content'] ?? '');

            if ($question !== '' && WordPressHtml::plainText($answer) !== '') {
                $items[] = ['question' => $question, 'answer' => $answer];
            }
        }

        return $items === [] ? [] : [['kind' => 'faq', 'items' => $items]];
    }

    /**
     * @param  array<mixed>  $settings
     * @return Item
     */
    private function tier(array $settings): array
    {
        $currency = match ($settings['currency_symbol'] ?? '') {
            'euro' => '€',
            'dollar' => '$',
            'custom' => (string) ($settings['currency_symbol_custom'] ?? ''),
            '' => '',
            default => (string) $settings['currency_symbol'],
        };

        $features = [];

        foreach ((array) ($settings['features_list'] ?? []) as $feature) {
            $text = WordPressHtml::inlineText((string) ($feature['item_text'] ?? ''));

            if ($text !== '') {
                $features[] = $text;
            }
        }

        $button = WordPressHtml::inlineText((string) ($settings['button_text'] ?? ''));
        $url = $this->link($this->settingUrl($settings['link'] ?? null));

        return [
            'kind' => 'tier',
            'name' => WordPressHtml::inlineText((string) ($settings['heading'] ?? '')),
            'price' => trim(trim((string) ($settings['price'] ?? '')).' '.$currency),
            'period' => WordPressHtml::inlineText((string) ($settings['period'] ?? '')) ?: null,
            'description' => WordPressHtml::inlineText((string) ($settings['sub_heading'] ?? '')) ?: null,
            'features' => $features,
            'cta' => $button !== '' && $url !== null ? ['label' => Str::limit($button, 60, ''), 'url' => $url] : null,
        ];
    }

    /**
     * @param  array<mixed>  $settings
     * @return Item
     */
    private function stat(array $settings): array
    {
        $number = (string) ($settings['ending_number'] ?? '');

        return [
            'kind' => 'stat',
            'value' => trim(($settings['prefix'] ?? '').$number.($settings['suffix'] ?? '')),
            'label' => WordPressHtml::inlineText((string) ($settings['title'] ?? '')),
        ];
    }

    /**
     * A progress bar: its share as the figure ("78%"), its title the label.
     * The stats block draws a share's hairline filled to it. Without a share
     * or a title it says nothing: dropped and counted.
     *
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function share(array $settings): array
    {
        $percent = $settings['percent']['size'] ?? null;
        $label = WordPressHtml::inlineText((string) ($settings['title'] ?? ''));

        if (! is_numeric($percent) || $label === '') {
            return $this->skip('progress', $settings);
        }

        return [['kind' => 'stat', 'value' => $percent.'%', 'label' => $label]];
    }

    /**
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function gallery(array $settings): array
    {
        $images = [];
        $lists = [$settings['gallery'] ?? [], $settings['carousel'] ?? []];

        foreach ((array) ($settings['galleries'] ?? []) as $gallery) {
            $lists[] = is_array($gallery) ? ($gallery['multiple_gallery'] ?? []) : [];
        }

        foreach ($lists as $list) {
            foreach ((array) $list as $image) {
                if (is_array($image) && (! empty($image['id']) || ! empty($image['url']))) {
                    $images[] = ['id' => (int) ($image['id'] ?? 0), 'url' => (string) ($image['url'] ?? '')];
                }
            }
        }

        if ($images === []) {
            $this->report->drop('widget:gallery (empty or dynamic)');

            return [];
        }

        return [['kind' => 'gallery', 'images' => $images]];
    }

    /**
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function video(array $settings): array
    {
        $type = (string) ($settings['video_type'] ?? 'youtube');
        $url = (string) ($settings['youtube_url'] ?? '');

        if ($type === 'youtube' && stripos($url, 'youtu') !== false) {
            return [['kind' => 'video', 'url' => $url]];
        }

        $this->report->drop("widget:video ({$type})");

        return [];
    }

    /**
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function button(array $settings): array
    {
        // "Solicita oferta <p>cu o singura cerere…</p>": the label is the part before the markup.
        $raw = (string) ($settings['text'] ?? '');
        $label = WordPressHtml::inlineText((string) preg_replace('/<(p|br|span)\b.*$/is', '', $raw)) ?: WordPressHtml::inlineText($raw);
        $href = $this->settingUrl($settings['link'] ?? null);
        $popup = str_contains((string) json_encode($settings['__dynamic__'] ?? []), 'popup');

        $url = $href !== null ? $this->link($href) : null;

        if ($url === null && $popup) {
            $url = self::POPUP_TARGET;
            $this->report->note("The button “{$label}” opened the quote popup; it links to ".self::POPUP_TARGET.'.');
        }

        if ($label === '' || $url === null) {
            $this->report->drop('widget:button (no link)');

            return [];
        }

        return [['kind' => 'button', 'label' => Str::limit($label, 60, ''), 'url' => $url]];
    }

    /**
     * @param  array<mixed>  $settings
     * @return list<Item>
     */
    private function skip(string $type, array $settings): array
    {
        $this->report->drop("widget:{$type}");
        $this->countStagingLinks($settings);

        return [];
    }

    /**
     * Links to the stale staging domain inside something dropped whole.
     *
     * @param  array<mixed>  $dropped
     */
    private function countStagingLinks(array $dropped): void
    {
        $this->report->drop('link:staging (in a dropped element)', substr_count((string) json_encode($dropped, JSON_UNESCAPED_SLASHES), WordPressHtml::STAGING_HOST));
    }

    /**
     * Two columns: one with a single content image, the other with headings
     * and text only.
     *
     * @param  list<array<mixed>>  $children
     * @return Item|null
     */
    private function imageText(array $children, ?string $link): ?array
    {
        if (count($children) !== 2) {
            return null;
        }

        $sides = [];

        foreach ($children as $child) {
            $widgets = array_values(array_filter((array) ($child['elements'] ?? []), is_array(...)));

            if (! in_array($child['elType'] ?? null, ['column', 'container'], true)
                || array_filter($widgets, fn (array $w): bool => ($w['elType'] ?? null) !== 'widget') !== []) {
                return null;
            }

            $sides[] = $widgets;
        }

        foreach ([0, 1] as $imageSide) {
            $images = array_values(array_filter($sides[$imageSide], fn (array $w): bool => ($w['widgetType'] ?? null) === 'image'
                && ! $this->isDecorative((array) ($w['settings'] ?? []))));
            $others = array_filter($sides[$imageSide], fn (array $w): bool => ! in_array($w['widgetType'] ?? null, ['image', 'spacer', 'divider'], true));
            $text = $sides[1 - $imageSide];
            $textTypes = array_unique(array_map(fn (array $w): string => (string) ($w['widgetType'] ?? ''), $text));

            if (count($images) !== 1 || $others !== [] || array_diff($textTypes, ['heading', 'text-editor', 'button', 'spacer', 'divider']) !== []
                || ! in_array('text-editor', $textTypes, true)) {
                continue;
            }

            $image = $this->image((array) ($images[0]['settings'] ?? []))[0] ?? null;
            $content = [];

            foreach ($text as $widget) {
                foreach ($this->widget($widget, (array) ($widget['settings'] ?? []), $link) as $item) {
                    $content[] = ['section' => 0, 'parent' => '', ...$item];
                }
            }

            $content = $this->dropEyebrows($content);

            if ($image === null || array_filter($content, fn (array $i): bool => $i['kind'] === 'text') === []) {
                return null;
            }

            return ['kind' => 'imageText', 'image' => $image, 'position' => $imageSide === 0 ? 'left' : 'right', 'items' => $content];
        }

        return null;
    }

    /**
     * A hand-built card: an image and a heading (and maybe a text) in one
     * container, usually linked as a whole.
     *
     * @param  list<array<mixed>>  $children
     * @return Item|null
     */
    private function card(string $type, array $children, ?string $link): ?array
    {
        $types = array_map(fn (array $w): string => ($w['elType'] ?? '') === 'widget' ? (string) ($w['widgetType'] ?? '') : '', $children);
        sort($types);

        if ($type !== 'container' || ($types !== ['heading', 'image'] && $types !== ['heading', 'image', 'text-editor'])) {
            return null;
        }

        $byType = [];

        foreach ($children as $widget) {
            $byType[$widget['widgetType']] = (array) ($widget['settings'] ?? []);
        }

        if ($this->isDecorative($byType['image'])) {
            return null;
        }

        return [
            'kind' => 'feature',
            'title' => WordPressHtml::inlineText((string) ($byType['heading']['title'] ?? '')),
            'text' => WordPressHtml::inlineText((string) ($byType['text-editor']['editor'] ?? '')),
            'link' => $this->settingUrl($byType['heading']['link'] ?? null) ?? $link,
        ];
    }

    // ---- Page-level passes --------------------------------------------------

    /**
     * Drops the old tables of contents, and the heading right above each.
     *
     * @param  list<Item>  $items
     * @return list<Item>
     */
    private function dropTablesOfContents(array $items): array
    {
        $kept = [];

        foreach ($items as $item) {
            if ($item['kind'] !== 'toc') {
                $kept[] = $item;

                continue;
            }

            $this->report->drop('widget:icon-list (table of contents)');
            $previous = end($kept);

            if ($previous !== false && $previous['kind'] === 'heading'
                && ($previous['section'] === $item['section'] || preg_match(self::TOC_HEADING, $previous['text']) === 1)) {
                array_pop($kept);
                $this->report->drop('heading (table of contents)');
            }
        }

        return $kept;
    }

    /**
     * @param  list<Item>  $items
     * @return list<Item>
     */
    private function dropEyebrows(array $items): array
    {
        $kept = [];

        foreach ($items as $i => $item) {
            $next = $items[$i + 1] ?? null;

            if ($item['kind'] === 'heading' && ($item['eyebrow'] ?? false)
                && $next !== null && $next['kind'] === 'heading' && $next['parent'] === $item['parent']) {
                $this->report->drop('heading (eyebrow)');

                continue;
            }

            $kept[] = $item;
        }

        return $kept;
    }

    /**
     * Each section holding a form becomes one form item, carrying its first
     * heading and the short text after it.
     *
     * @param  list<Item>  $items
     * @return list<Item>
     */
    private function collapseFormSections(array $items): array
    {
        $formSections = array_unique(array_column(array_filter($items, fn (array $i): bool => $i['kind'] === 'form'), 'section'));

        if ($formSections === []) {
            return $items;
        }

        $result = [];
        $done = [];

        foreach ($items as $item) {
            if (! in_array($item['section'], $formSections, true)) {
                $result[] = $item;

                continue;
            }

            if (isset($done[$item['section']])) {
                continue;
            }

            $done[$item['section']] = true;
            $section = array_values(array_filter($items, fn (array $i): bool => $i['section'] === $item['section']));
            $form = ['kind' => 'form', 'section' => $item['section'], 'parent' => '', 'heading' => null, 'intro' => null];

            foreach ($section as $member) {
                $text = match ($member['kind']) {
                    'heading' => $member['text'],
                    'text' => WordPressHtml::inlineText($member['html']),
                    default => null,
                };

                if ($form['heading'] === null && $member['kind'] === 'heading' && mb_strlen($member['text']) <= 160) {
                    $form['heading'] = $member['text'];

                    continue;
                }

                if ($form['heading'] !== null && $form['intro'] === null && $text !== null && $text !== '' && mb_strlen($text) <= 400 && ! str_starts_with($text, '#')) {
                    $form['intro'] = $text;

                    continue;
                }

                if ($member['kind'] !== 'form') {
                    $this->report->drop('form section: '.($member['contact'] ?? false ? 'contact details' : $member['kind']));
                }
            }

            $result[] = $form;
        }

        return $result;
    }

    /**
     * The hero's data, and the items left once it has taken its own.
     *
     * @param  list<Item>  $items
     * @return array{0: array<string, mixed>, 1: list<Item>}
     */
    private function hero(array $items, string $title, bool $titleIsHeading): array
    {
        $hero = ['heading' => $title, 'subheading' => null, 'image_asset_id' => null, 'primary_cta' => null, 'secondary_cta' => null];
        $index = null;

        if (! $titleIsHeading) {
            $items = $this->openImageTexts($items);

            foreach ($items as $i => $item) {
                if ($item['kind'] === 'heading' && $item['rank'] === 1) {
                    $index = $i;

                    break;
                }
            }

            foreach ($index === null ? $items : [] as $i => $item) {
                if ($item['kind'] === 'heading' && $item['section'] <= 1) {
                    $index = $i;

                    break;
                }
            }
        }

        if ($index === null) {
            return [$this->heroData($hero), $items];
        }

        $section = $items[$index]['section'];
        $hero['heading'] = $items[$index]['text'];
        $taken = [$index => true];
        $subheadingItems = [];
        $subheading = [];
        $buttons = [];
        $headingsOpen = true;

        foreach ($items as $i => $item) {
            if ($item['section'] !== $section || $i <= $index) {
                continue;
            }

            if ($item['kind'] === 'heading') {
                $large = ($item['styled'] ?? false) || $item['size'] >= 30 || $item['rank'] <= 2 && $i === $index + 1;

                if ($headingsOpen && $large && $subheading === [] && (str_word_count($item['text']) === 1 || preg_match('/^\p{Ll}/u', $item['text']) === 1)) {
                    $hero['heading'] .= ' '.$item['text'];
                    $taken[$i] = true;

                    continue;
                }

                if ($headingsOpen && $large) {
                    $subheading[] = $item['text'];
                    $subheadingItems[] = $i;
                    $taken[$i] = true;

                    continue;
                }

                $headingsOpen = false;

                continue;
            }

            if ($item['kind'] === 'text' && $headingsOpen && $subheading === []) {
                $text = WordPressHtml::inlineText($item['html']);

                if ($text !== '' && mb_strlen($text) <= 400) {
                    $subheading[] = $text;
                    $subheadingItems[] = $i;
                    $taken[$i] = true;
                }

                $headingsOpen = false;

                continue;
            }

            if ($item['kind'] === 'button' && count($buttons) < 2 && ! $this->headingBetween($items, $index, $i)) {
                $buttons[] = ['label' => $item['label'], 'url' => $item['url']];
                $taken[$i] = true;

                continue;
            }

            if ($item['kind'] === 'image' && $hero['image_asset_id'] === null) {
                $assetId = $this->html->resolveImage($item['id'], $item['url']);

                if ($assetId !== null) {
                    $hero['image_asset_id'] = $assetId;
                    $taken[$i] = true;
                }
            }
        }

        $joined = implode(' ', $subheading);
        $hero['subheading'] = $joined !== '' && mb_strlen($joined) <= 400 ? $joined : null;
        $hero['primary_cta'] = $buttons[0] ?? null;
        $hero['secondary_cta'] = $buttons[1] ?? null;

        if ($joined !== '' && $hero['subheading'] === null) {
            // Too long for the hero: the lines stay in the body.
            foreach ($subheadingItems as $i) {
                unset($taken[$i]);
            }
        }

        return [$this->heroData($hero), array_values(array_diff_key($items, $taken))];
    }

    /**
     * An opening section laid out as image + text is the hero itself: its
     * image and text go back to being items the hero can take.
     *
     * @param  list<Item>  $items
     * @return list<Item>
     */
    private function openImageTexts(array $items): array
    {
        $opening = $items[0]['section'] ?? null;
        $result = [];

        foreach ($items as $item) {
            if ($item['kind'] !== 'imageText' || $item['section'] !== $opening) {
                $result[] = $item;

                continue;
            }

            $members = array_map(fn (array $member): array => [...$member, 'section' => $item['section'], 'parent' => $item['parent']], $item['items']);
            $image = [...$item['image'], 'section' => $item['section'], 'parent' => $item['parent']];

            array_push($result, ...($item['position'] === 'left' ? [$image, ...$members] : [...$members, $image]));
        }

        return $result;
    }

    /**
     * @param  list<Item>  $items
     */
    private function headingBetween(array $items, int $from, int $to): bool
    {
        for ($i = $from + 1; $i < $to; $i++) {
            if ($items[$i]['kind'] === 'heading' && $items[$i]['section'] === $items[$from]['section'] && ! ($items[$i]['styled'] ?? false) && $items[$i]['size'] < 30) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $hero
     * @return array<string, mixed>
     */
    private function heroData(array $hero): array
    {
        $heading = (string) $hero['heading'];

        if (mb_strlen($heading) > 160) {
            $this->report->note('The hero heading was cut to 160 characters.');
            $heading = Str::limit($heading, 157, '…', preserveWords: true);
        }

        return [
            'eyebrow' => null,
            'heading' => $heading,
            'subheading' => $hero['subheading'],
            'image_asset_id' => $hero['image_asset_id'],
            'layout' => $hero['image_asset_id'] !== null ? 'split' : 'centered',
            // On the old site's purples, as the internal pages' heroes.
            'background' => 'gradient-violet',
            'align' => 'center',
            'logos' => false,
            'primary_cta' => $hero['primary_cta'],
            'secondary_cta' => $hero['secondary_cta'],
        ];
    }

    /**
     * Elementor heading ranks used on the page → h2, h3, h4.
     *
     * @param  list<Item>  $items
     * @return array<int, int>
     */
    private function headingLevels(array $items): array
    {
        $ranks = [];

        foreach ($items as $item) {
            foreach ($item['kind'] === 'imageText' ? $item['items'] : [$item] as $member) {
                if ($member['kind'] === 'heading') {
                    $ranks[$member['rank']] = true;
                }
            }
        }

        $ranks = array_keys($ranks);
        sort($ranks);

        $levels = [];

        foreach ($ranks as $position => $rank) {
            $levels[$rank] = min(4, $position + 2);
        }

        return $levels;
    }

    // ---- Building blocks ----------------------------------------------------

    /**
     * @param  list<Item>  $items
     * @return list<array<string, mixed>>
     */
    private function assemble(array $items, string $title): array
    {
        $this->title = $title;
        $this->blocks = [];
        $this->buffer = [];
        $this->group = null;
        $lastSection = null;

        foreach ($items as $item) {
            $kind = $item['kind'];
            $groupKind = match ($kind) {
                'feature' => 'features',
                'faq' => 'faq',
                'tier' => 'pricing_tiers',
                'stat' => 'stats',
                default => null,
            };

            if ($groupKind !== null) {
                $this->addToGroup($groupKind, $item);

                continue;
            }

            $this->flushGroup();

            switch ($kind) {
                case 'heading':
                case 'text':
                case 'html':
                case 'image':
                    $this->buffer[] = $item;
                    break;

                case 'gallery':
                    [$heading] = $this->lift($this->buffer, $item['section'], 0);
                    $this->flushBuffer();
                    array_push($this->blocks, ...$this->galleryBlocks($item['images'], $heading));
                    break;

                case 'video':
                    [$heading] = $this->lift($this->buffer, $item['section'], 0);
                    $this->flushBuffer();
                    $this->blocks[] = $this->block('video', ['title' => $heading ?? $title, 'url' => $item['url']]);
                    break;

                case 'imageText':
                    $this->placeImageText($item);
                    break;

                case 'form':
                    [$heading, $intro] = $item['heading'] === null ? $this->lift($this->buffer, $item['section'], 400) : [$item['heading'], $item['intro']];
                    $this->flushBuffer();
                    $this->blocks[] = $this->block('quote_form', array_filter(['heading' => $heading, 'intro' => $intro]) + ['show_contact_details' => true]);
                    break;

                case 'button':
                    $this->placeButton($item, $lastSection);
                    break;
            }

            $lastSection = $item['section'];
        }

        $this->flushGroup();
        $this->flushBuffer();

        return $this->blocks;
    }

    /**
     * Cards, questions, prices and counters gather into one block per run in
     * a section; a new run takes the heading (and intro) above it.
     *
     * @param  Item  $item
     */
    private function addToGroup(string $type, array $item): void
    {
        if ($this->group !== null && ($this->group['type'] !== $type || $this->group['section'] !== $item['section'])) {
            $this->flushGroup();
        }

        if ($this->group === null) {
            [$heading, $intro] = $this->lift($this->buffer, $item['section'], in_array($type, ['features', 'pricing_tiers'], true) ? 400 : 0);
            $this->flushBuffer();
            $this->group = ['type' => $type, 'section' => $item['section'], 'heading' => $heading, 'intro' => $intro, 'items' => []];
        }

        $this->group['items'] = $item['kind'] === 'faq'
            ? [...$this->group['items'], ...$item['items']]
            : [...$this->group['items'], $item];
    }

    private function flushGroup(): void
    {
        if ($this->group === null) {
            return;
        }

        [$blocks, $fallback] = $this->groupBlocks($this->group);
        $this->group = null;

        if ($fallback !== []) {
            array_push($this->buffer, ...$fallback);

            return;
        }

        $this->flushBuffer();
        array_push($this->blocks, ...$blocks);
    }

    private function flushBuffer(): void
    {
        array_push($this->blocks, ...$this->richTextBlocks($this->buffer, $this->title));
        $this->buffer = [];
    }

    /**
     * @param  Item  $item
     */
    private function placeImageText(array $item): void
    {
        $image = $item['image'];

        // Without its image, the text simply continues the rich text.
        if ($this->html->resolveImage($image['id'], $image['url']) === null) {
            $this->report->unresolved('image', $image['url'] !== '' ? $image['url'] : "attachment #{$image['id']}");

            foreach ($item['items'] as $member) {
                $this->buffer[] = ['section' => $item['section'], 'parent' => $item['parent'], ...$member];
            }

            return;
        }

        $own = $item['items'][0]['kind'] === 'heading' ? array_shift($item['items'])['text'] : null;
        [$heading] = $own === null ? $this->lift($this->buffer, $item['section'], 0) : [$own];
        $this->flushBuffer();
        $this->blocks[] = $this->imageTextBlock($item, $heading);
    }

    /**
     * A button under a heading starts a call to action; a second one joins
     * it; one after an image + text block becomes its link.
     *
     * @param  Item  $item
     */
    private function placeButton(array $item, ?int $lastSection): void
    {
        $cta = ['label' => $item['label'], 'url' => $item['url']];
        [$heading, $text] = $this->lift($this->buffer, $item['section'], 300, sameSection: true);

        if ($heading !== null) {
            $this->flushBuffer();
            $this->blocks[] = $this->block('cta_banner', ['heading' => $heading, 'text' => $text, 'primary_cta' => $cta, 'variant' => 'default']);

            return;
        }

        $last = array_key_last($this->blocks);

        if ($this->buffer === [] && $last !== null && $lastSection === $item['section']) {
            $data = $this->blocks[$last]['data'];

            if ($this->blocks[$last]['type'] === 'cta_banner' && $data['secondary_cta'] === null) {
                $this->blocks[$last]['data']['secondary_cta'] = $cta;

                return;
            }

            if ($this->blocks[$last]['type'] === 'image_text' && $data['cta'] === null) {
                $this->blocks[$last]['data']['cta'] = $cta;

                return;
            }
        }

        $this->report->drop('widget:button (no heading nearby)');
    }

    /**
     * Takes the heading (and, when $introMax > 0, a short text under it)
     * from the end of the buffer. A heading on its own may sit in the
     * previous section (the 2022 articles give every widget a section of its
     * own); a heading with an intro, or one a button reaches for, must share
     * the block's section.
     *
     * @param  list<Item>  $buffer
     * @return array{0: string|null, 1: string|null}
     */
    private function lift(array &$buffer, int $section, int $introMax, bool $sameSection = false): array
    {
        $count = count($buffer);
        $last = $buffer[$count - 1] ?? null;
        $before = $buffer[$count - 2] ?? null;

        if ($last !== null && $last['kind'] === 'heading' && (! $sameSection || $last['section'] === $section) && mb_strlen($last['text']) <= 160) {
            array_pop($buffer);

            return [$last['text'], null];
        }

        if ($introMax > 0 && $last !== null && $before !== null && $last['kind'] === 'text' && $before['kind'] === 'heading'
            && $last['section'] === $section && $before['section'] === $section && mb_strlen($before['text']) <= 160) {
            $intro = WordPressHtml::inlineText($last['html']);

            if ($intro !== '' && mb_strlen($intro) <= $introMax && substr_count(strtolower($last['html']), '<p') <= 1) {
                array_splice($buffer, -2);

                return [$before['text'], $intro];
            }
        }

        return [null, null];
    }

    /**
     * @param  list<Item>  $buffer
     * @return list<array<string, mixed>>
     */
    private function richTextBlocks(array $buffer, string $title): array
    {
        if ($buffer === []) {
            return [];
        }

        $blocks = [];

        foreach ($this->html->toSegments($this->bufferHtml($buffer), $this->report) as $segment) {
            $blocks[] = $segment['type'] === 'video'
                ? $this->block('video', ['title' => $title, 'url' => $segment['url']])
                : $this->block('rich_text', ['content' => $segment['doc']]);
        }

        return $blocks;
    }

    /**
     * @param  list<Item>  $items
     */
    private function bufferHtml(array $items): string
    {
        $html = '';

        foreach ($items as $item) {
            $html .= match ($item['kind']) {
                'heading' => $this->headingHtml($item),
                'text', 'html' => $item['html'],
                'image' => '<figure><img src="'.e($item['url']).'" data-attachment-id="'.$item['id'].'">'
                    .($item['caption'] !== null ? '<figcaption>'.e($item['caption']).'</figcaption>' : '').'</figure>',
                'button' => '<p>'.$this->linked(e($item['label']), $item['url']).'</p>',
                default => '',
            };
        }

        return $html;
    }

    /**
     * @param  Item  $item
     */
    private function headingHtml(array $item): string
    {
        $level = $this->levels[$item['rank']] ?? ($item['rank'] === 99 ? 3 : 2);

        return "<h{$level}>".$this->linked(e($item['text']), $item['link'] ?? null)."</h{$level}>";
    }

    /**
     * @param  array<string, mixed>  $group
     * @return array{0: list<array<string, mixed>>, 1: list<Item>} the blocks, or items to go into the rich text instead
     */
    private function groupBlocks(array $group): array
    {
        $heading = $group['heading'];
        $items = $group['items'];

        switch ($group['type']) {
            case 'features':
                $cards = [];

                foreach ($items as $item) {
                    $card = $this->card2feature($item);

                    if ($card === null) {
                        return [[], $this->featuresAsText($group)];
                    }

                    $cards[] = $card;
                }

                return [array_map(fn (array $chunk, int $i): array => $this->block('features', [
                    'heading' => $i === 0 ? $heading : null,
                    'intro' => $i === 0 ? $group['intro'] : null,
                    'layout' => 'columns',
                    'columns' => count($cards) % 4 === 0 && count($cards) % 3 !== 0 ? 4 : (count($cards) === 2 ? 2 : 3),
                    'items' => $chunk,
                ]), array_chunk($cards, 12), array_keys(array_chunk($cards, 12))), []];

            case 'faq':
                $faq = [];

                foreach ($items as $item) {
                    $answer = WordPressHtml::plainText($item['answer']);

                    if (mb_strlen($answer) > 2000 || mb_strlen($item['question']) > 200) {
                        return [[], $this->faqAsText($group)];
                    }

                    $faq[] = ['question' => $item['question'], 'answer' => $answer];
                }

                return [array_map(fn (array $chunk, int $i): array => $this->block('faq', ['heading' => $i === 0 && $heading !== null ? $heading : 'Întrebări frecvente', 'items' => $chunk]), array_chunk($faq, 30), array_keys(array_chunk($faq, 30))), []];

            case 'stats':
                $stats = array_map(fn (array $item): array => ['value' => Str::limit($item['value'], 20, ''), 'label' => Str::limit($item['label'], 80, ''), 'note' => null], $items);

                return [array_map(fn (array $chunk, int $i): array => $this->block('stats', ['heading' => $i === 0 ? $heading : null, 'items' => $chunk]), array_chunk($stats, 6), array_keys(array_chunk($stats, 6))), []];

            case 'pricing_tiers':
                $tiers = array_map(fn (array $item): array => [
                    'name' => Str::limit($item['name'], 60, ''),
                    'price' => Str::limit($item['price'] !== '' ? $item['price'] : '—', 40, ''),
                    'period' => $item['period'],
                    'description' => $item['description'],
                    'features' => array_slice(array_map(fn (string $f): string => Str::limit($f, 120, ''), $item['features']), 0, 20),
                    'highlighted' => false,
                    'cta' => $item['cta'],
                ], $items);

                return [array_map(fn (array $chunk, int $i): array => $this->block('pricing_tiers', [
                    'heading' => $i === 0 ? $heading : null,
                    'intro' => $i === 0 ? $group['intro'] : null,
                    'tiers' => $chunk,
                ]), array_chunk($tiers, 4), array_keys(array_chunk($tiers, 4))), []];
        }

        return [[], []];
    }

    /**
     * @param  Item  $item
     * @return array<string, mixed>|null
     */
    private function card2feature(array $item): ?array
    {
        $title = $item['title'];
        $text = $item['text'];

        if ($title === '') {
            [$title, $text] = mb_strlen($text) <= 100 ? [$text, ''] : ['', $text];
        }

        if ($title === '' || mb_strlen($title) > 100 || mb_strlen($text) > 400) {
            return null;
        }

        $url = $this->link($item['link']);

        return [
            'icon' => null,
            'title' => $title,
            'text' => $text !== '' ? $text : null,
            'note' => null,
            'link' => $url !== null ? ['label' => 'Află mai multe', 'url' => $url] : null,
        ];
    }

    /**
     * Cards that don't fit the features block, as headings and paragraphs.
     *
     * @param  array<string, mixed>  $group
     * @return list<Item>
     */
    private function featuresAsText(array $group): array
    {
        $this->report->note('Cards too long for a features block were kept as rich text.');
        $items = $this->groupHeading($group);

        foreach ($group['items'] as $card) {
            if ($card['title'] !== '') {
                $items[] = ['kind' => 'heading', 'text' => $card['title'], 'rank' => 99, 'section' => $group['section'], 'link' => null];
            }

            if ($card['text'] !== '') {
                $items[] = ['kind' => 'html', 'html' => '<p>'.e($card['text']).'</p>', 'section' => $group['section']];
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $group
     * @return list<Item>
     */
    private function faqAsText(array $group): array
    {
        $this->report->note('A question group with a long answer was kept as rich text.');
        $items = $this->groupHeading($group);

        foreach ($group['items'] as $qa) {
            $items[] = ['kind' => 'heading', 'text' => $qa['question'], 'rank' => 99, 'section' => $group['section'], 'link' => null];
            $items[] = ['kind' => 'html', 'html' => $qa['answer'], 'section' => $group['section']];
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $group
     * @return list<Item>
     */
    private function groupHeading(array $group): array
    {
        $items = [];

        if ($group['heading'] !== null) {
            $items[] = ['kind' => 'heading', 'text' => $group['heading'], 'rank' => 2, 'section' => $group['section'], 'link' => null];
        }

        if ($group['intro'] !== null) {
            $items[] = ['kind' => 'html', 'html' => '<p>'.e($group['intro']).'</p>', 'section' => $group['section']];
        }

        return $items;
    }

    /**
     * @param  list<array{id: int, url: string}>  $images
     * @return list<array<string, mixed>>
     */
    private function galleryBlocks(array $images, ?string $heading): array
    {
        $resolved = [];

        foreach ($images as $image) {
            $assetId = $this->html->resolveImage($image['id'], $image['url']);

            if ($assetId === null) {
                $this->report->unresolved('image', $image['url'] !== '' ? $image['url'] : "attachment #{$image['id']}");

                continue;
            }

            $resolved[$assetId] = ['asset_id' => $assetId, 'caption' => null];
        }

        if ($resolved === []) {
            $this->report->drop('widget:gallery (no image resolved)');

            return [];
        }

        $chunks = array_chunk(array_values($resolved), 30);

        return array_map(fn (array $chunk, int $i): array => $this->block('gallery', [
            'heading' => $i === 0 ? $heading : null,
            'columns' => 3,
            'images' => $chunk,
        ]), $chunks, array_keys($chunks));
    }

    /**
     * @param  Item  $item  an image + text item whose image resolves
     * @return array<string, mixed>
     */
    private function imageTextBlock(array $item, ?string $heading): array
    {
        $assetId = $this->html->resolveImage($item['image']['id'], $item['image']['url']);
        $buttons = array_values(array_filter($item['items'], fn (array $i): bool => $i['kind'] === 'button'));
        $content = array_values(array_filter($item['items'], fn (array $i): bool => $i['kind'] !== 'button'));
        $nodes = [];

        foreach ($this->html->toSegments($this->bufferHtml($content), $this->report) as $segment) {
            if ($segment['type'] === 'doc') {
                array_push($nodes, ...$segment['doc']['content']);
            } else {
                $this->report->drop('video inside image + text');
            }
        }

        return $this->block('image_text', [
            'heading' => $heading,
            'content' => ['type' => 'doc', 'content' => $nodes],
            'image_asset_id' => $assetId,
            'image_position' => $item['position'],
            'cta' => isset($buttons[0]) ? ['label' => $buttons[0]['label'], 'url' => $buttons[0]['url']] : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function block(string $type, array $data): array
    {
        $block = $this->registry->make($type);
        $block['data'] = [...$block['data'], ...$data];

        return $block;
    }

    /**
     * A page's opening line or two, left by the old layout in a section of
     * its own under the hero ("Reprezinta o metoda…" under "SEO = Search
     * Engine Optimization"): moved into the hero's subheading, a paragraph
     * each. Only a rich text of paragraphs alone — at most two, 400
     * characters — right after the hero, and only while the subheading
     * stays within its 800. Not on articles: their first rich text is the
     * article.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    private function foldIntoHero(array $blocks): array
    {
        $nodes = ($blocks[1]['type'] ?? null) === 'rich_text' ? ($blocks[1]['data']['content']['content'] ?? []) : [];
        $text = function (array $node) use (&$text): string {
            return ($node['text'] ?? '').implode('', array_map($text, $node['content'] ?? []));
        };

        // A link would be lost in the plain-text subheading: such a text stays.
        if ($nodes === [] || count($nodes) > 2 || array_unique(array_column($nodes, 'type')) !== ['paragraph']
            || str_contains((string) json_encode($nodes), '"type":"link"')) {
            return $blocks;
        }

        $lines = array_values(array_filter(array_map(fn (array $node): string => trim($text($node)), $nodes)));
        $subheading = implode("\n\n", array_filter([trim((string) ($blocks[0]['data']['subheading'] ?? '')), ...$lines]));

        if ($lines === [] || mb_strlen(implode(' ', $lines)) > 400 || mb_strlen($subheading) > 800) {
            return $blocks;
        }

        $blocks[0]['data']['subheading'] = $subheading;
        array_splice($blocks, 1, 1);
        $this->report->drop('rich text (into the hero)');

        return $blocks;
    }

    /**
     * The old FAQ sections come apart in three: a rich text with the
     * section's heading ("Intrebari frecvente") and a line under it, the
     * questions, and the section's illustration on its own. Folded back
     * into the faq block: the line its intro; the heading (the faq has its
     * own) and the illustration dropped and counted. Only a rich text that
     * is that and no more — an FAQ-like heading, at most two short
     * paragraphs, images — right before the questions; only images right
     * after them.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    private function foldFaqSections(array $blocks): array
    {
        $text = function (array $node) use (&$text): string {
            return ($node['text'] ?? '').implode('', array_map($text, $node['content'] ?? []));
        };
        $nodes = fn (?array $block): array => ($block['type'] ?? null) === 'rich_text' ? ($block['data']['content']['content'] ?? []) : [];
        $images = fn (array $list): int => count(array_filter($list, fn (array $node): bool => $node['type'] === 'assetImage'));

        $out = [];

        for ($i = 0; $i < count($blocks); $i++) {
            $block = $blocks[$i];

            if ($block['type'] !== 'faq') {
                $out[] = $block;

                continue;
            }

            $before = $nodes($out[array_key_last($out) ?? 0] ?? null);
            $first = $before[0] ?? null;
            $paragraphs = array_values(array_filter($before, fn (array $node): bool => $node['type'] === 'paragraph'));
            $rest = array_filter(array_slice($before, 1), fn (array $node): bool => ! in_array($node['type'], ['paragraph', 'assetImage'], true));

            if (
                ($first['type'] ?? null) === 'heading'
                && preg_match('/^\s*(([iî]ntreb[aă]ri)|faq|q\s*&\s*a)\b/iu', $text($first)) === 1
                && $rest === []
                && count($paragraphs) <= 2
                && mb_strlen(implode(' ', array_map($text, $paragraphs))) <= 300
            ) {
                array_pop($out);
                $intro = trim(implode(' ', array_map($text, $paragraphs)));
                $block['data']['intro'] = ($block['data']['intro'] ?? null) ?: ($intro !== '' ? $intro : null);
                $this->report->drop('rich text (the FAQ\'s heading and intro)');
                $this->report->drop('image (beside the FAQ)', $images($before));
            }

            $out[] = $block;

            $after = $nodes($blocks[$i + 1] ?? null);

            if ($after !== [] && $images($after) === count($after)) {
                $this->report->drop('image (beside the FAQ)', count($after));
                $i++;
            }
        }

        return $out;
    }

    // ---- Helpers ------------------------------------------------------------

    /**
     * A link's final href, or null.
     */
    private function link(?string $href): ?string
    {
        return $href === null ? null : $this->html->rewriteLink($href, $this->report);
    }

    private function linked(string $escapedText, ?string $href): string
    {
        return $href === null ? $escapedText : '<a href="'.e($href).'">'.$escapedText.'</a>';
    }

    private function settingUrl(mixed $link): ?string
    {
        $url = is_array($link) ? trim((string) ($link['url'] ?? '')) : '';

        return $url !== '' ? $url : null;
    }

    private function isContactLink(string $url): bool
    {
        return preg_match('~^(tel|mailto):|^https?://(maps\.app\.goo\.gl|goo\.gl/maps|(www\.)?google\.[a-z.]+/maps)~i', trim($url)) === 1;
    }

    /**
     * Clouds, planets and other images placed as backdrop: Elementor gives
     * them an opacity, or takes them out of the flow without a caption (the
     * captioned rocket beside the home page's title is content).
     *
     * @param  array<mixed>  $settings
     */
    private function isDecorative(array $settings): bool
    {
        return isset($settings['opacity'])
            || (in_array($settings['_position'] ?? null, ['absolute', 'fixed'], true) && trim((string) ($settings['caption'] ?? '')) === '');
    }

    /**
     * @param  array<mixed>  $settings
     */
    private function fontSize(array $settings): int
    {
        foreach (['typography_font_size', 'title_typography_font_size', 'words_typography_font_size'] as $key) {
            $size = is_array($settings[$key] ?? null) ? ($settings[$key]['size'] ?? null) : null;

            if (is_numeric($size)) {
                return (int) $size;
            }
        }

        return 0;
    }
}
