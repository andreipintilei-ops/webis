<?php

namespace App\Blocks;

/**
 * Named, recognisable clients (public institutions by default) — a trust
 * signal that reads as a list rather than a logo wall.
 */
class NotableClientsBlock extends Block
{
    public static function type(): string
    {
        return 'notable_clients';
    }

    public static function label(): string
    {
        return 'Clienți notabili';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'intro' => null,
            'source' => 'institutions',
            'client_ids' => [],
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}intro" => $this->text(false, 400),
            "{$p}source" => $this->oneOf(['institutions', 'manual']),
            ...$this->picked($p, 'client_ids', 'clients', 40),
        ];
    }
}
