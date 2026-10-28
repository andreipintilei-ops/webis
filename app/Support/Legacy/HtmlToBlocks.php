<?php

namespace App\Support\Legacy;

use App\Blocks\BlockRegistry;

/**
 * A classic or Gutenberg HTML body (the projects, and any page without
 * Elementor) as blocks: `rich_text`, split by a `video` wherever a YouTube
 * embed stood, after a `hero` when the page needs one. Projects have none:
 * their template prints the title itself.
 */
final class HtmlToBlocks
{
    public function __construct(
        private readonly WordPressHtml $html,
        private readonly BlockListValidator $validator,
        private readonly BlockRegistry $registry,
    ) {}

    public function convert(string $html, string $title, bool $withHero = true): Conversion
    {
        $report = new ConversionReport;
        $blocks = [];

        if ($withHero) {
            $blocks[] = $this->block('hero', ['heading' => $title, 'layout' => 'centered']);
        }

        foreach ($this->html->toSegments($html, $report) as $segment) {
            $blocks[] = $segment['type'] === 'doc'
                ? $this->block('rich_text', ['content' => $segment['doc']])
                : $this->block('video', ['title' => $title, 'url' => $segment['url']]);
        }

        return new Conversion($this->validator->validate($blocks, $report), $report);
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
