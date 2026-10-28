<?php

namespace App\Blocks;

use App\Support\RichText\RichText;

/**
 * An image beside a short rich-text passage, image on either side.
 */
class ImageTextBlock extends Block
{
    public static function type(): string
    {
        return 'image_text';
    }

    public static function label(): string
    {
        return 'Imagine și text';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'content' => RichText::emptyDocument(),
            'image_asset_id' => null,
            'image_position' => 'right',
            'cta' => null,
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            ...$this->richText($p, 'content'),
            "{$p}image_asset_id" => $this->asset(required: true),
            "{$p}image_position" => $this->oneOf(['left', 'right']),
            ...$this->cta($p, 'cta'),
        ];
    }

    protected function assetFields(): array
    {
        return ['image_asset_id'];
    }

    protected function richTextFields(): array
    {
        return ['content'];
    }
}
