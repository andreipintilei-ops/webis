<?php

namespace App\Blocks;

use Illuminate\Validation\Rule;

/**
 * Recent or chosen blog posts — internal links from money pages to articles.
 */
class BlogTeaserBlock extends Block
{
    public static function type(): string
    {
        return 'blog_teaser';
    }

    public static function label(): string
    {
        return 'Articole din blog';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'source' => 'latest',
            'category_id' => null,
            'post_ids' => [],
            'limit' => 3,
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}source" => $this->oneOf(['latest', 'category', 'manual']),
            "{$p}category_id" => ["required_if:{$p}source,category", 'nullable', 'integer', Rule::exists('post_categories', 'id')],
            ...$this->picked($p, 'post_ids', 'posts', 6),
            "{$p}limit" => ['required', 'integer', 'min:1', 'max:6'],
        ];
    }
}
