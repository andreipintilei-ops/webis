<?php

namespace App\Blocks;

use Illuminate\Validation\Rule;

class GalleryBlock extends Block
{
    public static function type(): string
    {
        return 'gallery';
    }

    public static function label(): string
    {
        return 'Galerie';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'columns' => 3,
            'images' => [],
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}columns" => $this->oneOf(['2', '3', '4']),
            "{$p}images" => ['required', 'array', 'min:1', 'max:30'],
            "{$p}images.*" => ['array:asset_id,caption'],
            "{$p}images.*.asset_id" => ['required', 'integer', Rule::exists('assets', 'id')],
            "{$p}images.*.caption" => $this->text(false, 200),
        ];
    }

    protected function assetFields(): array
    {
        return ['images.*.asset_id'];
    }
}
