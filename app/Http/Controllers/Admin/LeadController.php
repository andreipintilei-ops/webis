<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Support\AdminTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The quote-request inbox. Spam is kept (status `spam`) so false positives
 * can be recovered, but hidden unless asked for.
 */
class LeadController extends Controller
{
    /**
     * GET /admin/cereri
     */
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $leads = $this->query($request, $filters)
            ->with('assignee:id,name')
            ->latest('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Lead $lead): array => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'service' => $lead->service_label,
                'budget' => $lead->budget,
                'message' => Str::limit((string) $lead->message, 140),
                'status' => $lead->status->value,
                'status_label' => $lead->status->label(),
                'assignee' => $lead->assignee?->name,
                'created_at' => AdminTime::display($lead->created_at),
            ]);

        $counts = Lead::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('admin/leads/Index', [
            'leads' => $leads,
            'filters' => $filters,
            'statuses' => array_map(fn (LeadStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($counts[$status->value] ?? 0),
            ], LeadStatus::cases()),
        ]);
    }

    /**
     * GET /admin/cereri/{lead}
     */
    public function show(Lead $lead): Response
    {
        $lead->load(['assignee:id,name', 'servicePage:id,title']);

        return Inertia::render('admin/leads/Show', [
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'service' => $lead->service_label,
                'service_page_url' => $lead->servicePage ? route('admin.pages.edit', $lead->servicePage->id) : null,
                'budget' => $lead->budget,
                'message' => $lead->message,
                'source_url' => $lead->source_url,
                'referrer' => $lead->referrer,
                'utm' => $lead->utm ?? [],
                'gclid' => $lead->gclid,
                'consent_at' => AdminTime::display($lead->consent_at),
                'ip_address' => $lead->ip_address,
                'user_agent' => $lead->user_agent,
                'status' => $lead->status->value,
                'notes' => $lead->notes,
                'assigned_to' => $lead->assigned_to,
                'created_at' => AdminTime::display($lead->created_at),
            ],
            'statuses' => array_map(
                fn (LeadStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                LeadStatus::cases(),
            ),
            'users' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => $user->name])
                ->values()->all(),
        ]);
    }

    /**
     * PATCH /admin/cereri/{lead}
     */
    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validate([
            'status' => ['required', new Enum(LeadStatus::class)],
            'notes' => ['nullable', 'string', 'max:10000'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cererea a fost actualizată.']);

        return back();
    }

    /**
     * DELETE /admin/cereri/{lead} — e.g. a GDPR erasure request.
     */
    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cererea a fost ștearsă.']);

        return to_route('admin.leads.index');
    }

    /**
     * GET /admin/cereri/export — the current filter as CSV. UTF-8 with BOM and
     * `;` separators, so Excel with Romanian settings opens it with diacritics
     * and columns intact.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->query($request, $this->filters($request))->with('assignee:id,name')->oldest('id');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Nume', 'Email', 'Telefon', 'Firmă', 'Serviciu', 'Buget', 'Mesaj', 'Stare', 'Responsabil', 'Sursă', 'Referrer', 'UTM source', 'UTM medium', 'UTM campaign', 'gclid'], ';');

            $query->chunk(500, function ($leads) use ($out): void {
                foreach ($leads as $lead) {
                    /** @var Lead $lead */
                    fputcsv($out, [
                        AdminTime::display($lead->created_at),
                        $lead->name,
                        $lead->email,
                        $lead->phone,
                        $lead->company,
                        $lead->service_label,
                        $lead->budget,
                        $lead->message,
                        $lead->status->label(),
                        $lead->assignee?->name,
                        $lead->source_url,
                        $lead->referrer,
                        $lead->utm['utm_source'] ?? null,
                        $lead->utm['utm_medium'] ?? null,
                        $lead->utm['utm_campaign'] ?? null,
                        $lead->gclid,
                    ], ';');
                }
            });

            fclose($out);
        }, 'cereri-'.now(AdminTime::ZONE)->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{q: string, status: string, mine: bool}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => LeadStatus::tryFrom((string) $request->query('status', ''))->value ?? '',
            'mine' => $request->boolean('mine'),
        ];
    }

    /**
     * @param  array{q: string, status: string, mine: bool}  $filters
     * @return Builder<Lead>
     */
    private function query(Request $request, array $filters): Builder
    {
        return Lead::query()
            // Spam only when asked for by name.
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']),
                fn (Builder $query) => $query->where('status', '!=', LeadStatus::Spam->value))
            ->when($filters['q'] !== '', fn (Builder $query) => $query->where(fn (Builder $where) => $where
                ->where('name', 'like', "%{$filters['q']}%")
                ->orWhere('email', 'like', "%{$filters['q']}%")
                ->orWhere('phone', 'like', "%{$filters['q']}%")
                ->orWhere('company', 'like', "%{$filters['q']}%")))
            ->when($filters['mine'], fn (Builder $query) => $query->where('assigned_to', $request->user()?->id));
    }
}
