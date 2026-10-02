<?php

namespace App\Blocks;

/**
 * How a project runs, as numbered steps.
 */
class ProcessStepsBlock extends Block
{
    public static function type(): string
    {
        return 'process_steps';
    }

    public static function label(): string
    {
        return 'Etape de lucru';
    }

    public function defaults(): array
    {
        return [
            'heading' => null,
            'intro' => null,
            'steps' => [],
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}intro" => $this->text(false, 400),
            "{$p}steps" => ['required', 'array', 'min:1', 'max:10'],
            "{$p}steps.*" => ['array:title,text'],
            "{$p}steps.*.title" => $this->text(true, 100),
            "{$p}steps.*.text" => $this->text(false, 500),
        ];
    }
}
