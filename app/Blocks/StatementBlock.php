<?php

namespace App\Blocks;

use Illuminate\Validation\Rule;

/**
 * A two-column statement: a small label on the left (it stays in view while
 * the column beside it scrolls), a large statement on the right — its words
 * light up as it scrolls through — then a paragraph and a link. "Despre noi"
 * on the home page.
 *
 * `background`: "none" (the page's white), or "hero" — see-through, white
 * on the opening hero's fixed background, when it follows that hero directly
 * (blocks.blade.php decides; elsewhere it is white).
 */
class StatementBlock extends Block
{
    public const array BACKGROUNDS = ['none', 'hero'];

    public static function type(): string
    {
        return 'statement';
    }

    public static function label(): string
    {
        return 'Declarație';
    }

    public function defaults(): array
    {
        return [
            'eyebrow' => null,
            'statement' => '',
            'text' => null,
            'link' => null,
            'background' => 'none',
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}eyebrow" => $this->text(false, 60),
            "{$p}statement" => $this->text(true, 300),
            "{$p}text" => $this->text(false, 800),
            ...$this->cta($p, 'link'),
            "{$p}background" => ['nullable', Rule::in(self::BACKGROUNDS)],
        ];
    }
}
