<?php

namespace App\Blocks;

/**
 * Cards in columns: a title, a sentence or two and, optionally, a note (the
 * line that says when it fits — "Când…") and a link at the foot. Used for
 * benefits and for what the company builds ("Ce dezvoltăm").
 */
class FeaturesBlock extends Block
{
    public static function type(): string
    {
        return 'features';
    }

    public static function label(): string
    {
        return 'Avantaje';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'intro' => null,
            'columns' => 3,
            'items' => [],
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}intro" => $this->text(false, 400),
            "{$p}columns" => $this->oneOf(['2', '3', '4']),
            "{$p}items" => ['required', 'array', 'min:1', 'max:12'],
            "{$p}items.*" => ['array:icon,title,text,note,link'],
            "{$p}items.*.icon" => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/'],
            "{$p}items.*.title" => $this->text(true, 100),
            "{$p}items.*.text" => $this->text(false, 400),
            "{$p}items.*.note" => $this->text(false, 200),
            ...$this->cta($p, 'items.*.link'),
        ];
    }
}
