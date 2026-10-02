<?php

namespace App\Blocks;

/**
 * A strip of client logos — every client marked "show in logos", or picked.
 */
class ClientLogosBlock extends Block
{
    public static function type(): string
    {
        return 'client_logos';
    }

    public static function label(): string
    {
        return 'Logo-uri clienți';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'source' => 'all',
            'client_ids' => [],
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}source" => $this->oneOf(['all', 'manual']),
            ...$this->picked($p, 'client_ids', 'clients', 40),
        ];
    }
}
