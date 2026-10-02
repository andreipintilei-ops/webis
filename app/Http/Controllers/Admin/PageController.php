<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\PageType;
use App\Http\Controllers\Admin\Concerns\EditsContent;
use App\Http\Controllers\Admin\Concerns\ManagesTrash;
use App\Http\Controllers\Admin\Concerns\SavesOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use App\Support\AdminChoices;
use App\Support\AdminTime;
use App\Support\PublicUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    use EditsContent, ManagesTrash, SavesOrder;

    /**
     * GET /admin/pagini — filter by type to see (and drag) the order used on
     * the site; the trash lists deleted pages for restore.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $type = PageType::tryFrom((string) $request->query('type', ''));
        $status = ContentStatus::tryFrom((string) $request->query('status', ''));
        $trashed = $request->boolean('trashed');

        $pages = Page::query()
            ->when($trashed, fn (Builder $query) => $query->onlyTrashed())
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $where) => $where
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")))
            ->when($type, fn (Builder $query, PageType $type) => $query->where('type', $type->value))
            ->when($status, fn (Builder $query, ContentStatus $status) => $query->where('status', $status->value))
            ->when($type, fn (Builder $query) => $query->orderBy('sort_order')->orderBy('title'), fn (Builder $query) => $query->latest('updated_at'))
            ->paginate($type ? 200 : 25)
            ->withQueryString()
            ->through(fn (Page $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'url' => PublicUrls::for($page),
                'type' => $page->type->value,
                'type_label' => $page->type->label(),
                'status' => $page->status->value,
                'status_label' => $page->status->label(),
                'is_live' => $page->isPublished(),
                'published_at' => AdminTime::display($page->published_at),
                'updated_at' => AdminTime::display($page->updated_at),
                'trashed' => $page->trashed(),
            ]);

        return Inertia::render('admin/pages/Index', [
            'pages' => $pages,
            'filters' => [
                'q' => $search,
                'type' => $type->value ?? '',
                'status' => $status->value ?? '',
                'trashed' => $trashed,
            ],
            'types' => $this->typeOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * GET /admin/pagini/creeaza?type=service
     */
    public function create(Request $request): Response
    {
        return $this->editor(new Page([
            'type' => PageType::tryFrom((string) $request->query('type')) ?? PageType::Page,
            'title' => '',
            'slug' => '',
            'blocks' => [],
        ]));
    }

    /**
     * POST /admin/pagini
     */
    public function store(PageRequest $request): RedirectResponse
    {
        $page = DB::transaction(function () use ($request): Page {
            $page = Page::create($request->pageAttributes());
            $page->projects()->sync($this->pivotOrder($request->projectIds()));

            return $page;
        });

        $this->announceIfNewlyPublished($page, false);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pagina a fost creată.']);

        return to_route('admin.pages.edit', $page->id);
    }

    /**
     * GET /admin/pagini/{page}/editeaza
     */
    public function edit(Page $page): Response
    {
        return $this->editor($page->load('projects:id'));
    }

    /**
     * PUT /admin/pagini/{page}
     */
    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $wasPublished = $page->isPublished();

        DB::transaction(function () use ($request, $page): void {
            $page->update($request->pageAttributes());
            $page->projects()->sync($this->pivotOrder($request->projectIds()));
        });

        $this->announceIfNewlyPublished($page, $wasPublished);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modificările au fost salvate.']);

        return to_route('admin.pages.edit', $page->id);
    }

    public function destroy(Page $page): RedirectResponse
    {
        return $this->trash($page);
    }

    public function restore(Page $page): RedirectResponse
    {
        return $this->untrash($page);
    }

    public function forceDestroy(Page $page): RedirectResponse
    {
        return $this->purge($page);
    }

    /**
     * POST /admin/pagini/ordine — ids in their new order.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $this->saveOrder(Page::class, $request);

        return back();
    }

    protected function routePrefix(): string
    {
        return 'pages';
    }

    protected function trashMessages(): array
    {
        return [
            'trashed' => 'Pagina a fost mutată în coș.',
            'restored' => 'Pagina a fost restaurată.',
            'purged' => 'Pagina a fost ștearsă definitiv.',
        ];
    }

    private function editor(Page $page): Response
    {
        return Inertia::render('admin/pages/Edit', [
            'page' => [
                'id' => $page->exists ? $page->id : null,
                'type' => $page->type->value,
                'title' => $page->title,
                'slug' => $page->slug,
                'excerpt' => $page->excerpt,
                'icon' => $page->icon,
                'hero_asset_id' => $page->hero_asset_id,
                'blocks' => $page->blocks ?? [],
                'details' => [
                    'price_from' => $page->details['price_from'] ?? null,
                    'service_type' => $page->details['service_type'] ?? null,
                ],
                'sort_order' => $page->sort_order ?? 0,
                'project_ids' => $page->exists ? $page->projects->pluck('id')->all() : [],
                'seo' => $page->seo->toArray(),
                'url' => $page->exists ? PublicUrls::for($page) : null,
                ...$this->publicationPayload($page),
            ],
            'types' => $this->typeOptions(),
            'statuses' => $this->statusOptions(),
            'blockTypes' => $this->blockTypes(),
            'choices' => AdminChoices::all(),
            'revisions' => $this->revisionsFor($page),
            'titleSuffix' => $this->titleSuffix(),
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return array_map(
            fn (PageType $type): array => ['value' => $type->value, 'label' => $type->label()],
            PageType::cases(),
        );
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, array{sort_order: int}>
     */
    private function pivotOrder(array $ids): array
    {
        $order = [];

        foreach ($ids as $position => $id) {
            $order[$id] = ['sort_order' => $position];
        }

        return $order;
    }
}
