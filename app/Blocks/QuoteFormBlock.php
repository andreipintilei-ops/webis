<?php

namespace App\Blocks;

use Illuminate\Validation\Rule;

/**
 * The quote-request form (a Vue island, Phase 5). On a service page it can
 * preselect that service.
 */
class QuoteFormBlock extends Block
{
    public static function type(): string
    {
        return 'quote_form';
    }

    public static function label(): string
    {
        return 'Formular cerere ofertă';
    }

    public function defaults(): array
    {
        return [
            'heading' => 'Cere o ofertă',
            'intro' => null,
            'service_page_id' => null,
            'show_contact_details' => true,
        ];
    }

    public function rules(string $p): array
    {
        return [
            "{$p}heading" => $this->text(false, 160),
            "{$p}intro" => $this->text(false, 400),
            "{$p}service_page_id" => ['nullable', 'integer', Rule::exists('pages', 'id')],
            "{$p}show_contact_details" => ['required', 'boolean'],
        ];
    }
}
