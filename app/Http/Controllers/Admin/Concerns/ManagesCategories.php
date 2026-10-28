<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Http\Requests\Admin\ContentRequest;
use App\Models\PostCategory;
use App\Models\ProjectCategory;
use App\Support\PublicUrls;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Portfolio and blog categories are managed the same way: one list page with
 * an edit panel. The using controller supplies the model and its wording.
 */
trait ManagesCategories
{
    /**
     * @return class-string<ProjectCategory|PostCategory>
     */
    abstract protected function model(): string;

    /**
     * @return array{title: string, itemsLabel: string, relation: string, routePrefix: string}
     */
    abstract protected function config(): array;

    public function index(): Response
    {
        $config = $this->config();

        $categories = $this->model()::query()
            ->withCount($config['relation'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ProjectCategory|PostCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'sort_order' => $category->sort_order,
                'seo' => $category->seo->toArray(),
                'url' => PublicUrls::for($category),
                'items_count' => $category->getAttribute($config['relation'].'_count'),
                'update_url' => route("admin.{$config['routePrefix']}.update", $category->id),
                'destroy_url' => route("admin.{$config['routePrefix']}.destroy", $category->id),
            ]);

        return Inertia::render('admin/categories/Index', [
            'title' => $config['title'],
            'itemsLabel' => $config['itemsLabel'],
            'urlPrefix' => rtrim(PublicUrls::for(new ($this->model())(['slug' => ''])), '/').'/',
            'storeUrl' => route("admin.{$config['routePrefix']}.store"),
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->model()::create($this->validated($request, null));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoria a fost creată.']);

        return back();
    }

    public function update(Request $request, int $category): RedirectResponse
    {
        $model = $this->model()::query()->findOrFail($category);
        $model->update($this->validated($request, $model->id));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoria a fost salvată.']);

        return back();
    }

    /**
     * Items in the category stay; they simply lose it.
     */
    public function destroy(int $category): RedirectResponse
    {
        $this->model()::query()->findOrFail($category)->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoria a fost ștearsă.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId): array
    {
        $table = (new ($this->model()))->getTable();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:190', 'regex:'.ContentRequest::SLUG_PATTERN, Rule::unique($table, 'slug')->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'seo' => ['nullable', 'array'],
            'seo.title' => ['nullable', 'string', 'max:120'],
            'seo.description' => ['nullable', 'string', 'max:320'],
            'seo.noindex' => ['boolean'],
        ], [
            'slug.unique' => 'Adresa este folosită de altă categorie.',
            'slug.regex' => 'Doar litere mici, cifre și cratime.',
        ]);

        return [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'seo' => SeoData::fromArray(is_array($data['seo'] ?? null) ? $data['seo'] : []),
        ];
    }
}
