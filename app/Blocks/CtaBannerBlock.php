<?php

namespace App\Blocks;

/**
 * A full-width call to action, usually last on the page.
 */
class CtaBannerBlock extends Block
{
    public static function type(): string
    {
        return 'cta_banner';
    }

    public static function label(): string
    {
        return 'Îndemn (CTA)';
    }

    public function defaults(): array
    {
        return [
            'heading' => '',
            'text' => null,
            'primary_cta' => ['label' => 'Cere o ofertă', 'url' => ''],
            'secondary_cta' => null,
            'variant' => 'default',
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(true, 160),
            "{$p}text" => $this->text(false, 300),
            ...$this->cta($p, 'primary_cta', required: true),
            ...$this->cta($p, 'secondary_cta'),
            "{$p}variant" => $this->oneOf(['default', 'accent']),
        ];
    }
}
