<?php

namespace App\Support\Legacy\Import;

use App\Blocks\BlockRegistry;
use App\Console\Commands\ConvertLegacyContent;
use App\Support\Legacy\BlockListValidator;
use App\Support\Legacy\ContentSource;
use App\Support\Legacy\Conversion;
use App\Support\Legacy\ElementorToBlocks;
use App\Support\Legacy\HtmlToBlocks;
use App\Support\Legacy\PodsToBlocks;
use App\Support\Legacy\WordPressHtml;
use App\Support\RichText\RichText;

/**
 * The import's body converter, on top of the content converters: Pods fields
 * for the industry pages (`domenii`), Elementor for the articles and pages
 * that have it, classic HTML for the rest and for the projects. Images and
 * links resolve through the import's real resolvers (the media map, the
 * reviewed URL map), and block validation checks that the assets exist.
 *
 * What the converters drop or cannot resolve comes back in the ConvertedBody
 * for the report; an unresolved image never fails the import.
 */
final class LegacyBodyConverter implements BodyConverter
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly RichText $richText,
    ) {}

    public function available(): bool
    {
        return true;
    }

    /**
     * @param  string  $kind  'article', 'page', 'industry' or 'project'
     */
    public function convert(LegacyPost $post, string $kind, ConversionContext $context): ConvertedBody
    {
        $html = new WordPressHtml($context->media, $context->urls, $this->richText);
        $validator = new BlockListValidator($this->registry);
        $elementor = $post->meta('_elementor_data');

        $conversion = match (true) {
            $kind === 'industry' => (new PodsToBlocks($html, $validator, $this->registry))->convert($this->fields($post), $post->title),
            $kind !== 'project' && ContentSource::hasElementorData($elementor) => (new ElementorToBlocks($html, $validator, $this->registry))
                // Articles: the theme printed the title as the h1, so it is the hero.
                ->convert((string) $elementor, $post->title, $kind === 'article'),
            default => (new HtmlToBlocks($html, $validator, $this->registry))->convert($post->content, $post->title, $kind !== 'project'),
        };

        return $this->body($conversion);
    }

    private function body(Conversion $conversion): ConvertedBody
    {
        $report = $conversion->report;
        $warnings = $report->notes();

        foreach ($report->unresolvedItems() as $what => $urls) {
            $warnings[] = count($urls)." unresolved {$what}(s)";
        }

        return new ConvertedBody(
            blocks: $conversion->blocks,
            warnings: $warnings,
            dropped: $report->dropped(),
            wordCount: ConvertLegacyContent::blockWords($conversion->blocks),
            unresolved: $report->unresolvedItems(),
        );
    }

    /**
     * The post's meta as PodsToBlocks reads it: one value as a string, a
     * key stored more than once as a list.
     *
     * @return array<string, string|list<string>>
     */
    private function fields(LegacyPost $post): array
    {
        $fields = [];

        foreach ($post->meta as $key => $values) {
            if (! str_starts_with($key, 'litespeed')) {
                $fields[$key] = count($values) === 1 ? $values[0] : $values;
            }
        }

        return $fields;
    }
}
