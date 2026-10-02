<?php

namespace App\Blocks;

/**
 * Questions and answers. A page with this block also gets FAQPage JSON-LD
 * (Phase 4), so answers are plain text — exactly what the markup carries.
 */
class FaqBlock extends Block
{
    public static function type(): string
    {
        return 'faq';
    }

    public static function label(): string
    {
        return 'Întrebări frecvente';
    }

    public function defaults(): array
    {
        return [
            'heading' => 'Întrebări frecvente',
            'items' => [],
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}items" => ['required', 'array', 'min:1', 'max:30'],
            "{$p}items.*" => ['array:question,answer'],
            "{$p}items.*.question" => $this->text(true, 200),
            "{$p}items.*.answer" => $this->text(true, 2000),
        ];
    }
}
