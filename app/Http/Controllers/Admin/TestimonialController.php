<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SavesOrder;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Testimonials: a small list edited in a side panel, ordered by drag.
 */
class TestimonialController extends Controller
{
    use SavesOrder;

    /**
     * GET /admin/testimoniale?edit={id}
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $trashed = $request->boolean('trashed');

        $testimonials = Testimonial::query()
            ->with(['client:id,name', 'project:id,title'])
            ->when($trashed, fn (Builder $query) => $query->onlyTrashed())
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $where) => $where
                ->where('author_name', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhere('quote', 'like', "%{$search}%")))
            ->orderBy('sort_order')
            ->latest('id')
            ->get()
            ->map(fn (Testimonial $testimonial): array => [
                'id' => $testimonial->id,
                'author_name' => $testimonial->author_name,
                'author_role' => $testimonial->author_role,
                'company' => $testimonial->company,
                'quote' => $testimonial->quote,
                'rating' => $testimonial->rating,
                'avatar_asset_id' => $testimonial->avatar_asset_id,
                'client_id' => $testimonial->client_id,
                'client' => $testimonial->client?->name,
                'project_id' => $testimonial->project_id,
                'project' => $testimonial->project?->title,
                'is_visible' => $testimonial->is_visible,
                'sort_order' => $testimonial->sort_order,
                'trashed' => $testimonial->trashed(),
            ]);

        return Inertia::render('admin/testimonials/Index', [
            'testimonials' => $testimonials,
            'filters' => ['q' => $search, 'trashed' => $trashed],
            'editId' => $request->integer('edit') ?: null,
            'clients' => Client::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Client $client): array => ['value' => (string) $client->id, 'label' => $client->name])
                ->values()->all(),
            'projects' => Project::query()->orderBy('title')->get(['id', 'title'])
                ->map(fn (Project $project): array => ['value' => (string) $project->id, 'label' => $project->title])
                ->values()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Testimonial::create($this->validated($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Testimonialul a fost adăugat.']);

        return back();
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($this->validated($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Testimonialul a fost salvat.']);

        return back();
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Testimonialul a fost mutat în coș.']);

        return back();
    }

    public function restore(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->restore();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Testimonialul a fost restaurat.']);

        return back();
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->saveOrder(Testimonial::class, $request);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:150'],
            'author_role' => ['nullable', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'quote' => ['required', 'string', 'max:2000'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'avatar_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'is_visible' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            ...$data,
            'is_visible' => $request->boolean('is_visible'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
