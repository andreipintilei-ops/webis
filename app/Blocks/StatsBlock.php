<?php

namespace App\Blocks;

use App\Support\RichText\RichText;
use Illuminate\Validation\Rule;

/**
 * Big numbers with a label: "250+ site-uri livrate", and an optional passage
 * under them that reads them, in the same section, on the indigo or the
 * lavender.
 */
class StatsBlock extends Block
{
    public static function type(): string
    {
        return 'stats';
    }

    public static function label(): string
    {
        return 'Cifre';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'items' => [],
            'body' => RichText::emptyDocument(),
            'tone' => 'dark',
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}items" => ['required', 'array', 'min:1', 'max:6'],
            "{$p}items.*" => ['array:value,label,note'],
            "{$p}items.*.value" => $this->text(true, 20),
            "{$p}items.*.label" => $this->text(true, 80),
            "{$p}items.*.note" => $this->text(false, 160),
            ...$this->richText($p, 'body', required: false),
            // Optional: blocks from before it, and the import's, are dark.
            "{$p}tone" => ['sometimes', Rule::in(['dark', 'light'])],
        ];
    }

    protected function richTextFields(): array
    {
        return ['body'];
    }
}
