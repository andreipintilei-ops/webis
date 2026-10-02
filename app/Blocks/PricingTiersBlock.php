<?php

namespace App\Blocks;

use App\Rules\SafeLink;

/**
 * Packages with a starting price. Prices are free text ("de la 2.500 lei")
 * because agency pricing is rarely a single number.
 */
class PricingTiersBlock extends Block
{
    public static function type(): string
    {
        return 'pricing_tiers';
    }

    public static function label(): string
    {
        return 'Pachete și prețuri';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'intro' => null,
            'tiers' => [],
            'note' => null,
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}intro" => $this->text(false, 400),
            "{$p}tiers" => ['required', 'array', 'min:1', 'max:4'],
            "{$p}tiers.*" => ['array:name,price,period,description,features,highlighted,cta'],
            "{$p}tiers.*.name" => $this->text(true, 60),
            "{$p}tiers.*.price" => $this->text(true, 40),
            "{$p}tiers.*.period" => $this->text(false, 40),
            "{$p}tiers.*.description" => $this->text(false, 300),
            "{$p}tiers.*.features" => ['nullable', 'array', 'max:20'],
            // Blank lines from the one-per-line textarea are allowed here and
            // dropped in prepare().
            "{$p}tiers.*.features.*" => $this->text(false, 120),
            "{$p}tiers.*.highlighted" => ['nullable', 'boolean'],
            "{$p}tiers.*.cta" => ['nullable', 'array:label,url'],
            "{$p}tiers.*.cta.label" => $this->text(false, 60),
            "{$p}tiers.*.cta.url" => ['nullable', 'string', 'max:2048', new SafeLink],
            // e.g. "Prețurile nu includ TVA."
            "{$p}note" => $this->text(false, 300),
        ];
    }

    public function prepare(array $data): array
    {
        $data = parent::prepare($data);

        if (is_array($data['tiers'])) {
            $data['tiers'] = array_map(function (mixed $tier): mixed {
                if (is_array($tier) && is_array($tier['features'] ?? null)) {
                    $tier['features'] = array_values(array_filter(
                        array_map(fn (mixed $feature): string => trim((string) $feature), $tier['features']),
                        fn (string $feature): bool => $feature !== '',
                    ));
                }

                return $tier;
            }, $data['tiers']);
        }

        return $data;
    }
}
