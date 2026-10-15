<?php

namespace App\Http\Requests\Admin;

use App\Blocks\BlockRegistry;
use App\Enums\ProjectType;
use App\Models\Project;
use Illuminate\Validation\Rule;

class ProjectRequest extends ContentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('project');
        $blocks = $this->input('blocks');

        return [
            'title' => ['required', 'string', 'max:200'],
            ...$this->slugRules('projects', $project instanceof Project ? $project->id : null),
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'type' => ['required', Rule::enum(ProjectType::class)],
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
            'url' => ['nullable', 'url:https,http', 'max:255'],
            'summary' => ['nullable', 'string', 'max:200'],
            'need' => ['nullable', 'string', 'max:2000'],
            'built' => ['nullable', 'string', 'max:2000'],
            // One comma-separated line in the editor: "Laravel, Vue, MySQL".
            'stack' => ['nullable', 'string', 'max:500'],
            'cover_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'images' => ['nullable', 'array', 'max:30'],
            'images.*' => ['required', 'integer', 'distinct', Rule::exists('assets', 'id')],
            ...app(BlockRegistry::class)->rules(is_array($blocks) ? $blocks : []),
            'metrics' => ['array', 'max:6'],
            'metrics.*' => ['array:label,value'],
            'metrics.*.value' => ['required', 'string', 'max:30'],
            'metrics.*.label' => ['required', 'string', 'max:80'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'category_ids' => ['array', 'max:10'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('project_categories', 'id')],
            ...$this->publicationRules(),
            ...$this->seoRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Adresa este folosită de alt proiect (poate din coș).',
            'slug.regex' => 'Doar litere mici, cifre și cratime, ex. magazin-online-exemplu.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function projectAttributes(): array
    {
        return [
            'title' => $this->string('title')->trim()->toString(),
            'slug' => $this->string('slug')->toString(),
            'client_id' => $this->input('client_id'),
            'type' => $this->enum('type', ProjectType::class),
            'year' => $this->input('year'),
            'url' => $this->input('url'),
            'summary' => $this->input('summary'),
            'need' => $this->input('need'),
            'built' => $this->input('built'),
            'stack' => $this->stack(),
            'cover_asset_id' => $this->input('cover_asset_id'),
            'images' => array_values(array_map(intval(...), (array) $this->input('images', []))) ?: null,
            'blocks' => app(BlockRegistry::class)->prepare((array) $this->input('blocks', [])),
            'metrics' => array_values((array) $this->input('metrics', [])),
            'is_featured' => $this->boolean('is_featured'),
            'sort_order' => (int) $this->input('sort_order', 0),
            'seo' => $this->seo(),
            ...$this->publication(),
        ];
    }

    /**
     * @return list<int>
     */
    public function categoryIds(): array
    {
        return array_values(array_map(intval(...), (array) $this->input('category_ids', [])));
    }

    /**
     * The technologies, from their comma-separated line: trimmed, without
     * blanks or repeats; null when there are none.
     *
     * @return list<string>|null
     */
    private function stack(): ?array
    {
        $items = array_values(array_unique(array_filter(
            array_map(trim(...), explode(',', $this->string('stack')->toString())),
            fn (string $item): bool => $item !== '',
        )));

        return $items === [] ? null : $items;
    }
}
