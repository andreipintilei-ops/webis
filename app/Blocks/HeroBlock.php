<?php

namespace App\Blocks;

use Illuminate\Validation\Rule;

/**
 * The opening section: the page's one <h1>, a short promise and up to two
 * buttons. Its image is the LCP candidate and is preloaded.
 *
 * `background` (centred layout only): "none" — the image if one is chosen,
 * else the page's white; "gradient" — the animated WebGL gradient
 * (js/public/soffit.ts), which takes the place of the image, in the blues;
 * "gradient-violet" — the same in the old site's purples (webis.ro).
 *
 * `align` (centred layout only): the text "center" or "left" — the latter at
 * the left of the page's content column (--content-max).
 *
 * `logos` (centred layout only): a marquee of the clients marked "show in
 * logos" along the bottom of the hero.
 */
class HeroBlock extends Block
{
    public const array BACKGROUNDS = ['none', 'gradient', 'gradient-violet'];

    public const array ALIGNS = ['center', 'left'];

    public static function type(): string
    {
        return 'hero';
    }

    public static function label(): string
    {
        return 'Hero (deschidere)';
    }

    public function defaults(): array
    {
        return [
            'eyebrow' => null,
            'heading' => '',
            'subheading' => null,
            'image_asset_id' => null,
            'layout' => 'split',
            'background' => 'none',
            'align' => 'center',
            'logos' => false,
            'primary_cta' => null,
            'secondary_cta' => null,
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}eyebrow" => $this->text(false, 80),
            "{$p}heading" => $this->text(true, 160),
            "{$p}subheading" => $this->text(false, 400),
            "{$p}image_asset_id" => $this->asset(),
            "{$p}layout" => $this->oneOf(['split', 'centered']),
            "{$p}background" => ['nullable', Rule::in(self::BACKGROUNDS)],
            "{$p}align" => ['nullable', Rule::in(self::ALIGNS)],
            "{$p}logos" => ['nullable', 'boolean'],
            ...$this->cta($p, 'primary_cta'),
            ...$this->cta($p, 'secondary_cta'),
        ];
    }

    protected function assetFields(): array
    {
        return ['image_asset_id'];
    }

    /**
     * The centred hero's backdrop: "gradient", "image" or "none".
     *
     * @param  array<string, mixed>  $data
     */
    public static function backdrop(array $data): string
    {
        if (($data['layout'] ?? null) !== 'centered') {
            return 'none';
        }

        if (in_array($data['background'] ?? null, ['gradient', 'gradient-violet'], true)) {
            return 'gradient';
        }

        return empty($data['image_asset_id']) ? 'none' : 'image';
    }

    /**
     * The animated gradient's colours (js/public/soffit.ts): "violet" for
     * "gradient-violet", else "blue".
     *
     * @param  array<string, mixed>  $data
     */
    public static function palette(array $data): string
    {
        return ($data['background'] ?? null) === 'gradient-violet' ? 'violet' : 'blue';
    }

    /**
     * Whether the hero is text over a dark backdrop (an image or the gradient),
     * so it is set white and the header above it switches to light-on-dark.
     *
     * @param  array<string, mixed>  $data
     */
    public static function isDark(array $data): bool
    {
        return self::backdrop($data) !== 'none';
    }

    /**
     * The header theme a page needs given its blocks: "dark" when it opens on
     * a dark hero, else "light".
     *
     * @param  array<mixed>|null  $blocks
     */
    public static function headerThemeFor(?array $blocks): string
    {
        $first = $blocks[0] ?? null;

        return is_array($first) && ($first['type'] ?? null) === self::type() && self::isDark((array) ($first['data'] ?? []))
            ? 'dark'
            : 'light';
    }
}
