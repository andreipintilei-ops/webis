<?php

namespace App\Http\Requests\Admin;

use App\Blocks\BlockRegistry;
use App\Enums\PageType;
use App\Models\Page;
use App\Support\ReservedSlugs;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class PageRequest extends ContentRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $page = $this->route('page');
        $pageId = $page instanceof Page ? $page->id : null;
        $blocks = $this->input('blocks');

        return [
            'type' => [
                'required', new Enum(PageType::class),
                // One home page. Checked among live pages only.
                Rule::unique('pages', 'type')
                    ->where('type', PageType::Home->value)
                    ->whereNull('deleted_at')
                    ->ignore($pageId),
            ],
            'title' => ['required', 'string', 'max:200'],
            ...$this->slugRules('pages', $pageId),
            'excerpt' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/'],
            'hero_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            ...app(BlockRegistry::class)->rules(is_array($blocks) ? $blocks : []),
            'details' => ['nullable', 'array:price_from,service_type'],
            'details.price_from' => ['nullable', 'string', 'max:40'],
            'details.service_type' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'project_ids' => ['array', 'max:24'],
            'project_ids.*' => ['integer', 'distinct', Rule::exists('projects', 'id')],
            ...$this->publicationRules(),
            ...$this->seoRules(),
        ];
    }

    /**
     * Pages live at the site root, so their slug must not collide with a route.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $slug = $this->string('slug')->toString();

                if ($this->input('type') !== PageType::Home->value && ReservedSlugs::contains($slug)) {
                    $validator->errors()->add('slug', 'Adresa „/'.$slug.'” este rezervată de site. Alege alta.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'type.unique' => 'Există deja o primă pagină.',
            'slug.unique' => 'Adresa este folosită de altă pagină (poate din coș).',
            'slug.regex' => 'Doar litere mici, cifre și cratime, ex. creare-site-web.',
        ];
    }

    /**
     * Attributes for Page::fill().
     *
     * @return array<string, mixed>
     */
    public function pageAttributes(): array
    {
        $details = array_filter((array) $this->input('details', []), fn (mixed $value): bool => $value !== null && $value !== '');

        return [
            'type' => PageType::from((string) $this->input('type')),
            'title' => $this->string('title')->trim()->toString(),
            'slug' => $this->string('slug')->toString(),
            'excerpt' => $this->input('excerpt'),
            'icon' => $this->input('icon'),
            'hero_asset_id' => $this->input('hero_asset_id'),
            'blocks' => app(BlockRegistry::class)->prepare((array) $this->input('blocks', [])),
            'details' => $details === [] ? null : $details,
            'sort_order' => (int) $this->input('sort_order', 0),
            'seo' => $this->seo(),
            ...$this->publication(),
        ];
    }

    /**
     * @return list<int>
     */
    public function projectIds(): array
    {
        return array_values(array_map(intval(...), (array) $this->input('project_ids', [])));
    }
}
