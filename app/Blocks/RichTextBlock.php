<?php

namespace App\Blocks;

use App\Support\RichText\RichText;

class RichTextBlock extends Block
{
    public static function type(): string
    {
        return 'rich_text';
    }

    public static function label(): string
    {
        return 'Text';
    }

    public function defaults(): array
    {
        return [
            'content' => RichText::emptyDocument(),
        ];
    }

    public function rules(string $p): array
    {
        return $this->richText($p, 'content');
    }

    protected function richTextFields(): array
    {
        return ['content'];
    }
}
