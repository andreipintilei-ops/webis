<?php

namespace App\Blocks;

use Illuminate\Validation\Rule;

/**
 * A strip of portfolio projects: featured, latest, one category's, or picked.
 */
class PortfolioShowcaseBlock extends Block
{
    public static function type(): string
    {
        return 'portfolio_showcase';
    }

    public static function label(): string
    {
        return 'Portofoliu';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'intro' => null,
            'source' => 'featured',
            'category_id' => null,
            'project_ids' => [],
            'limit' => 6,
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}intro" => $this->text(false, 400),
            "{$p}source" => $this->oneOf(['featured', 'latest', 'category', 'manual']),
            "{$p}category_id" => ["required_if:{$p}source,category", 'nullable', 'integer', Rule::exists('project_categories', 'id')],
            ...$this->picked($p, 'project_ids', 'projects', 12),
            "{$p}limit" => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
