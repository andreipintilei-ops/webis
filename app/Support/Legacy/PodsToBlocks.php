<?php

namespace App\Support\Legacy;

use App\Blocks\BlockRegistry;

/**
 * Rebuilds a `domenii` industry page from its Pods fields. The 29 pages kept
 * no body of their own: the "Single Post - Sablon Domenii" Elementor template
 * filled one fixed layout from these fields, and the blocks follow that
 * layout top to bottom:
 *
 * | Live template section                         | Fields                                      | Block                                                |
 * |-----------------------------------------------|---------------------------------------------|------------------------------------------------------|
 * | Opening: title, two texts, "Cere oferta", carousel | post title, `descriere_h1`, `descriere_mica_h1` | `hero` (subheading `descriere_h1` when ≤ 400 characters, the featured image, "Cere o ofertă" → /contact/), then `rich_text` with `descriere_mica_h1` (and `descriere_h1` when too long for the hero) |
 * | "Beneficii": heading, subtitle, six cards     | `titlu_h2`, `subtitlu_h2`, `beneficii_h2-_b1…6` | `features` (heading, intro, one titled card each)    |
 * | "Beneficii": image, heading, six toggles      | `titlu_h3`, `beneficii_h3_-_titlu_N` / `_descriere_N` | `features` (each toggle's list as one line); as `rich_text` if a list is too long for a card |
 * | "Proiecte": heading, text, gallery            | `titlu_h4`, `descriere_h4`, `galerie_h1`    | `rich_text` (h2 + text), then `gallery`              |
 * | Contact: form and a closing text              | `descriere_personalizata`                   | `rich_text`, then `quote_form`                       |
 *
 * The live template's gallery and opening carousel both showed `galerie_h1`;
 * `galerie_h4` was never displayed, and the template's `imagine_h3` and
 * background image have no slot in the blocks — all three are counted as
 * dropped. The closing block is the form the template ended on, not a
 * `cta_banner`: no field holds a call to action. The fields carry no Q&A,
 * so there is no `faq`.
 */
final class PodsToBlocks
{
    /** The quote popup the template's "Cere oferta" button opened. */
    private const array QUOTE_CTA = ['label' => 'Cere o ofertă', 'url' => '/contact/'];

    private ConversionReport $report;

    public function __construct(
        private readonly WordPressHtml $html,
        private readonly BlockListValidator $validator,
        private readonly BlockRegistry $registry,
    ) {}

    /**
     * @param  array<string, string|list<string>>  $fields  the post's meta; a key stored more than once holds a list
     */
    public function convert(array $fields, string $title): Conversion
    {
        $this->report = new ConversionReport;

        $intro = $this->text($fields, 'descriere_h1');
        $heroIntro = $intro !== '' && mb_strlen($intro) <= 400 ? $intro : null;
        $imageId = (int) $this->field($fields, '_thumbnail_id') ?: ($this->attachments($fields, 'imagine_reprezentativa')[0] ?? 0);
        $heroImage = $imageId > 0 ? $this->html->resolveImage($imageId, '') : null;

        if ($imageId > 0 && $heroImage === null) {
            $this->report->unresolved('image', "attachment #{$imageId}");
        }

        $blocks = [$this->block('hero', [
            'heading' => $title,
            'subheading' => $heroIntro,
            'image_asset_id' => $heroImage,
            'layout' => $heroImage !== null ? 'split' : 'centered',
            'primary_cta' => self::QUOTE_CTA,
        ])];

        $opening = ($heroIntro === null && $intro !== '' ? '<p>'.e($intro).'</p>' : '').$this->paragraphs($fields, 'descriere_mica_h1');
        array_push($blocks, ...$this->richText($opening));

        $benefits = [];

        foreach (range(1, 6) as $n) {
            $benefit = $this->text($fields, "beneficii_h2-_b{$n}");

            if ($benefit !== '') {
                $benefits[] = ['icon' => null, 'title' => $benefit, 'text' => null, 'note' => null, 'link' => null];
            }
        }

        if ($benefits !== []) {
            $blocks[] = $this->block('features', [
                'heading' => $this->text($fields, 'titlu_h2') ?: null,
                'intro' => $this->text($fields, 'subtitlu_h2') ?: null,
                'items' => $benefits,
            ]);
        }

        array_push($blocks, ...$this->packageFeatures($fields));

        $models = ($this->text($fields, 'titlu_h4') !== '' ? '<h2>'.e($this->text($fields, 'titlu_h4')).'</h2>' : '')
            .(string) $this->field($fields, 'descriere_h4');
        array_push($blocks, ...$this->richText($models));
        array_push($blocks, ...$this->gallery($this->attachments($fields, 'galerie_h1')));
        array_push($blocks, ...$this->richText($this->paragraphs($fields, 'descriere_personalizata')));

        $blocks[] = $this->block('quote_form', []);

        foreach (['galerie_h4', 'imagine_h3', 'imagine_background_prima_sectiune'] as $unused) {
            if ($this->attachments($fields, $unused) !== []) {
                $this->report->drop("pods:{$unused}");
            }
        }

        return new Conversion($this->validator->validate($blocks, $this->report), $this->report);
    }

    /**
     * The six "what you get" toggles as cards, each list on one line; as
     * rich text (a heading and the list each) if a list is too long.
     *
     * @param  array<string, string|list<string>>  $fields
     * @return list<array<string, mixed>>
     */
    private function packageFeatures(array $fields): array
    {
        $heading = $this->text($fields, 'titlu_h3');
        $cards = [];
        $html = $heading !== '' ? '<h2>'.e($heading).'</h2>' : '';
        $fits = true;

        foreach (range(1, 6) as $n) {
            $title = $this->text($fields, "beneficii_h3_-_titlu_{$n}");
            $list = (string) $this->field($fields, "beneficii_h3_-_descriere_{$n}");
            $lines = array_map(fn (string $line): string => ltrim($line, '– '), array_filter(explode("\n", WordPressHtml::plainText($list))));
            $text = implode(' · ', $lines);

            if ($title === '' && $text === '') {
                continue;
            }

            $fits = $fits && $title !== '' && mb_strlen($title) <= 100 && mb_strlen($text) <= 400;
            $cards[] = ['icon' => null, 'title' => $title, 'text' => $text !== '' ? $text : null, 'note' => null, 'link' => null];
            $html .= ($title !== '' ? '<h3>'.e($title).'</h3>' : '').$list;
        }

        if ($cards === []) {
            return [];
        }

        if (! $fits) {
            $this->report->note('A package list was too long for a card: the packages are kept as rich text.');

            return $this->richText($html);
        }

        return [$this->block('features', ['heading' => $heading !== '' ? $heading : null, 'items' => $cards])];
    }

    /**
     * @param  list<int>  $attachmentIds
     * @return list<array<string, mixed>>
     */
    private function gallery(array $attachmentIds): array
    {
        $images = [];

        foreach ($attachmentIds as $id) {
            $assetId = $this->html->resolveImage($id, '');

            if ($assetId === null) {
                $this->report->unresolved('image', "attachment #{$id}");

                continue;
            }

            $images[$assetId] = ['asset_id' => $assetId, 'caption' => null];
        }

        return $images === [] ? [] : [$this->block('gallery', ['columns' => 3, 'images' => array_slice(array_values($images), 0, 30)])];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function richText(string $html): array
    {
        $blocks = [];

        foreach ($this->html->toSegments($html, $this->report) as $segment) {
            $blocks[] = $segment['type'] === 'doc'
                ? $this->block('rich_text', ['content' => $segment['doc']])
                : $this->block('video', ['title' => 'Video', 'url' => $segment['url']]);
        }

        return $blocks;
    }

    /**
     * A plain-text field as paragraphs (it may also hold inline HTML).
     *
     * @param  array<string, string|list<string>>  $fields
     */
    private function paragraphs(array $fields, string $key): string
    {
        $value = trim((string) $this->field($fields, $key));

        return $value === '' ? '' : $value."\n\n";
    }

    /**
     * Pods stores a multi-image field twice: one postmeta row per id, and the
     * ordered list serialised under `_pods_{field}`. The list wins.
     *
     * @param  array<string, string|list<string>>  $fields
     * @return list<int>
     */
    private function attachments(array $fields, string $key): array
    {
        $ordered = AttachmentMetadata::unserialise($this->field($fields, "_pods_{$key}"));
        $ids = $ordered !== [] ? $ordered : (array) ($fields[$key] ?? []);

        return array_values(array_unique(array_filter(array_map(intval(...), array_filter($ids, is_numeric(...))))));
    }

    /**
     * @param  array<string, string|list<string>>  $fields
     */
    private function field(array $fields, string $key): ?string
    {
        $value = $fields[$key] ?? null;

        return is_array($value) ? ($value[0] ?? null) : $value;
    }

    /**
     * @param  array<string, string|list<string>>  $fields
     */
    private function text(array $fields, string $key): string
    {
        return WordPressHtml::inlineText((string) $this->field($fields, $key));
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
}
