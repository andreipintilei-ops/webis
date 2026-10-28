<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Seo\ImportRedirects;
use App\Enums\RedirectCode;
use App\Enums\RedirectMatchType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RedirectRequest;
use App\Models\Redirect;
use App\Support\AdminTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manual redirects (admin only). Mostly the old WordPress URLs; the 404 log
 * feeds new ones.
 */
class RedirectController extends Controller
{
    /**
     * GET /admin/redirectionari?source=/cale — `source` pre-fills a new
     * redirect (the 404 log's "create redirect" action).
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $code = RedirectCode::tryFrom($request->integer('code'));

        $redirects = Redirect::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $where) => $where
                ->where('source_path', 'like', "%{$search}%")
                ->orWhere('target', 'like', "%{$search}%")))
            ->when($code, fn (Builder $query, RedirectCode $code) => $query->where('status_code', $code->value))
            ->orderBy('source_path')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (Redirect $redirect): array => [
                'id' => $redirect->id,
                'source_path' => $redirect->source_path,
                'match_type' => $redirect->match_type->value,
                'target' => $redirect->target,
                'status_code' => $redirect->status_code->value,
                'hits' => $redirect->hits,
                'last_hit_at' => AdminTime::display($redirect->last_hit_at),
                'notes' => $redirect->notes,
            ]);

        return Inertia::render('admin/redirects/Index', [
            'redirects' => $redirects,
            'filters' => ['q' => $search, 'code' => $code ? (string) $code->value : ''],
            'codes' => array_map(fn (RedirectCode $c): array => ['value' => (string) $c->value, 'label' => $c->label()], RedirectCode::cases()),
            'matchTypes' => array_map(fn (RedirectMatchType $t): array => ['value' => $t->value, 'label' => $t->label()], RedirectMatchType::cases()),
            'prefillSource' => $request->query('source') ? Redirect::normalisePath((string) $request->query('source')) : null,
        ]);
    }

    public function store(RedirectRequest $request): RedirectResponse
    {
        Redirect::create($request->redirectAttributes());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Redirecționarea a fost adăugată.']);

        return back();
    }

    public function update(RedirectRequest $request, Redirect $redirect): RedirectResponse
    {
        $redirect->update($request->redirectAttributes());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Redirecționarea a fost salvată.']);

        return back();
    }

    public function destroy(Redirect $redirect): RedirectResponse
    {
        $redirect->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Redirecționarea a fost ștearsă.']);

        return back();
    }

    /**
     * POST /admin/redirectionari/import — CSV with columns
     * `sursa;destinatie[;cod[;tip]]` (comma also accepted). Existing sources are
     * updated; invalid rows are skipped and reported (ImportRedirects, which
     * `legacy:import --load-redirects` shares).
     */
    public function import(Request $request, ImportRedirects $importRedirects): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:2048', 'mimes:csv,txt'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        ['saved' => $saved, 'skipped' => $skipped] = $importRedirects((string) file_get_contents($file->getRealPath()));

        $message = "Importate: {$saved}.";

        if ($skipped !== []) {
            $message .= ' Rânduri sărite (adresă invalidă): '.implode(', ', array_slice($skipped, 0, 20)).(count($skipped) > 20 ? '…' : '').'.';
        }

        Inertia::flash('toast', ['type' => $skipped === [] ? 'success' : 'warning', 'message' => $message]);

        return back();
    }

    /**
     * GET /admin/redirectionari/export — the same format the import reads.
     */
    public function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['sursa', 'destinatie', 'cod', 'tip', 'accesari'], ';');

            Redirect::query()->orderBy('source_path')->chunk(500, function ($redirects) use ($out): void {
                foreach ($redirects as $redirect) {
                    /** @var Redirect $redirect */
                    fputcsv($out, [$redirect->source_path, $redirect->target, $redirect->status_code->value, $redirect->match_type->value, $redirect->hits], ';');
                }
            });

            fclose($out);
        }, 'redirectionari-'.now(AdminTime::ZONE)->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
