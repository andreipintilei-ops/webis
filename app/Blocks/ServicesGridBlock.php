<?php

namespace App\Blocks;

/**
 * Cards linking to service or industry pages — all published ones of a type,
 * in their sort order, or a hand-picked list.
 */
class ServicesGridBlock extends Block
{
    public static function type(): string
    {
        return 'services_grid';
    }

    public static function label(): string
    {
        return 'Grilă servicii';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'intro' => null,
            'source' => 'services',
            'page_ids' => [],
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}intro" => $this->text(false, 400),
            "{$p}source" => $this->oneOf(['services', 'industries', 'manual']),
            ...$this->picked($p, 'page_ids', 'pages'),
        ];
    }
}
