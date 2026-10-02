<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SavesOrder;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Clients: a small list edited in a side panel, ordered by drag.
 */
class ClientController extends Controller
{
    use SavesOrder;

    /**
     * GET /admin/clienti?edit={id}
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $trashed = $request->boolean('trashed');

        $clients = Client::query()
            ->with('logo.media')
            ->withCount(['projects', 'testimonials'])
            ->when($trashed, fn (Builder $query) => $query->onlyTrashed())
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'logo_asset_id' => $client->logo_asset_id,
                'logo_url' => $this->thumbUrl($client),
                'sector' => $client->sector,
                'url' => $client->url,
                'is_institution' => $client->is_institution,
                'show_in_logos' => $client->show_in_logos,
                'sort_order' => $client->sort_order,
                'projects_count' => $client->projects_count,
                'testimonials_count' => $client->testimonials_count,
                'trashed' => $client->trashed(),
            ]);

        return Inertia::render('admin/clients/Index', [
            'clients' => $clients,
            'filters' => ['q' => $search, 'trashed' => $trashed],
            'editId' => $request->integer('edit') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Client::create($this->validated($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clientul a fost adăugat.']);

        return back();
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($this->validated($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clientul a fost salvat.']);

        return back();
    }

    /**
     * Projects and testimonials keep pointing at a trashed client; they lose it
     * only on a permanent delete.
     */
    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clientul a fost mutat în coș.']);

        return back();
    }

    public function restore(Client $client): RedirectResponse
    {
        $client->restore();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clientul a fost restaurat.']);

        return back();
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->saveOrder(Client::class, $request);

        return back();
    }

    /**
     * The logo's admin thumbnail, or the original until the conversion exists.
     */
    private function thumbUrl(Client $client): ?string
    {
        $file = $client->logo?->file();

        if ($file === null) {
            return null;
        }

        return $file->hasGeneratedConversion('thumb') ? $file->getUrl('thumb') : $file->getUrl();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'logo_asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'sector' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'url:https,http', 'max:255'],
            'is_institution' => ['boolean'],
            'show_in_logos' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            ...$data,
            'is_institution' => $request->boolean('is_institution'),
            'show_in_logos' => $request->boolean('show_in_logos'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
