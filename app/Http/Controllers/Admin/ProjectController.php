<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Admin\Concerns\EditsContent;
use App\Http\Controllers\Admin\Concerns\ManagesTrash;
use App\Http\Controllers\Admin\Concerns\SavesOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Support\AdminChoices;
use App\Support\AdminTime;
use App\Support\PublicUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use EditsContent, ManagesTrash, SavesOrder;

    /**
     * GET /admin/portofoliu
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = ContentStatus::tryFrom((string) $request->query('status', ''));
        $category = (int) $request->query('category', 0);
        $featured = $request->boolean('featured');
        $trashed = $request->boolean('trashed');

        $projects = Project::query()
            ->with(['client:id,name', 'categories:id,name'])
            ->when($trashed, fn (Builder $query) => $query->onlyTrashed())
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $where) => $where
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhereHas('client', fn (Builder $client) => $client->where('name', 'like', "%{$search}%"))))
            ->when($status, fn (Builder $query, ContentStatus $status) => $query->where('status', $status->value))
            ->when($category > 0, fn (Builder $query) => $query->whereHas('categories', fn (Builder $c) => $c->whereKey($category)))
            ->when($featured, fn (Builder $query) => $query->where('is_featured', true))
            ->orderBy('sort_order')
            ->latest('id')
            // Unfiltered, the list is the site order and can be dragged — so
            // it comes in one page, or reordering one page would collide with
            // positions on the others.
            ->paginate($search === '' && ! $status && $category === 0 && ! $featured && ! $trashed ? 500 : 30)
            ->withQueryString()
            ->through(fn (Project $project): array => [
                'id' => $project->id,
                'title' => $project->title,
                'url' => PublicUrls::for($project),
                'client' => $project->client?->name,
                'year' => $project->year,
                'categories' => $project->categories->pluck('name')->all(),
                'is_featured' => $project->is_featured,
                'status' => $project->status->value,
                'status_label' => $project->status->label(),
                'is_live' => $project->isPublished(),
                'published_at' => AdminTime::display($project->published_at),
                'trashed' => $project->trashed(),
            ]);

        return Inertia::render('admin/projects/Index', [
            'projects' => $projects,
            'filters' => [
                'q' => $search,
                'status' => $status->value ?? '',
                'category' => $category > 0 ? (string) $category : '',
                'featured' => $featured,
                'trashed' => $trashed,
            ],
            'statuses' => $this->statusOptions(),
            'categories' => ProjectCategory::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
                ->map(fn (ProjectCategory $c): array => ['value' => (string) $c->id, 'label' => $c->name])
                ->all(),
        ]);
    }

    /**
     * GET /admin/portofoliu/creeaza
     */
    public function create(): Response
    {
        return $this->editor(new Project(['title' => '', 'slug' => '', 'blocks' => [], 'metrics' => []]));
    }

    /**
     * POST /admin/portofoliu
     */
    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request): Project {
            $project = Project::create($request->projectAttributes());
            $project->categories()->sync($request->categoryIds());

            return $project;
        });

        $this->announceIfNewlyPublished($project, false);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proiectul a fost creat.']);

        return to_route('admin.projects.edit', $project->id);
    }

    /**
     * GET /admin/portofoliu/{project}/editeaza
     */
    public function edit(Project $project): Response
    {
        return $this->editor($project->load('categories:id'));
    }

    /**
     * PUT /admin/portofoliu/{project}
     */
    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $wasPublished = $project->isPublished();

        DB::transaction(function () use ($request, $project): void {
            $project->update($request->projectAttributes());
            $project->categories()->sync($request->categoryIds());
        });

        $this->announceIfNewlyPublished($project, $wasPublished);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modificările au fost salvate.']);

        return to_route('admin.projects.edit', $project->id);
    }

    public function destroy(Project $project): RedirectResponse
    {
        return $this->trash($project);
    }

    public function restore(Project $project): RedirectResponse
    {
        return $this->untrash($project);
    }

    public function forceDestroy(Project $project): RedirectResponse
    {
        return $this->purge($project);
    }

    /**
     * POST /admin/portofoliu/ordine
     */
    public function reorder(Request $request): RedirectResponse
    {
        $this->saveOrder(Project::class, $request);

        return back();
    }

    protected function routePrefix(): string
    {
        return 'projects';
    }

    protected function trashMessages(): array
    {
        return [
            'trashed' => 'Proiectul a fost mutat în coș.',
            'restored' => 'Proiectul a fost restaurat.',
            'purged' => 'Proiectul a fost șters definitiv.',
        ];
    }

    private function editor(Project $project): Response
    {
        return Inertia::render('admin/projects/Edit', [
            'project' => [
                'id' => $project->exists ? $project->id : null,
                'title' => $project->title,
                'slug' => $project->slug,
                'client_id' => $project->client_id,
                'year' => $project->year,
                'url' => $project->url,
                'summary' => $project->summary,
                'cover_asset_id' => $project->cover_asset_id,
                'blocks' => $project->blocks ?? [],
                'metrics' => $project->metrics ?? [],
                'is_featured' => (bool) $project->is_featured,
                'sort_order' => $project->sort_order ?? 0,
                'category_ids' => $project->exists ? $project->categories->pluck('id')->all() : [],
                'seo' => $project->seo->toArray(),
                'public_url' => $project->exists ? PublicUrls::for($project) : null,
                ...$this->publicationPayload($project),
            ],
            'statuses' => $this->statusOptions(),
            'blockTypes' => $this->blockTypes(),
            'choices' => AdminChoices::all(),
            'revisions' => $this->revisionsFor($project),
            'titleSuffix' => $this->titleSuffix(),
        ]);
    }
}
