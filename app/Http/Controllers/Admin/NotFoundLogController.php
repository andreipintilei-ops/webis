<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotFoundLog;
use App\Models\Redirect;
use App\Support\AdminTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Paths that returned 404, most-hit first (admin only). The frequent real ones
 * become redirects; bot noise gets ignored.
 */
class NotFoundLogController extends Controller
{
    /**
     * GET /admin/erori-404
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $ignored = $request->boolean('ignored');

        $logs = NotFoundLog::query()
            ->where('is_ignored', $ignored)
            ->when($search !== '', fn (Builder $query) => $query->where('path', 'like', "%{$search}%"))
            ->orderByDesc('hits')
            ->orderByDesc('last_seen_at')
            ->paginate(50)
            ->withQueryString();

        // Paths that have gained a redirect since they were logged.
        $redirected = Redirect::query()
            ->whereIn('source_path', $logs->getCollection()->pluck('path'))
            ->pluck('source_path')
            ->flip();

        return Inertia::render('admin/not-found/Index', [
            'logs' => $logs->through(fn (NotFoundLog $log): array => [
                'id' => $log->id,
                'path' => $log->path,
                'hits' => $log->hits,
                'last_seen_at' => AdminTime::display($log->last_seen_at),
                'first_seen_at' => AdminTime::display($log->created_at),
                'last_referrer' => $log->last_referrer,
                'is_ignored' => $log->is_ignored,
                'has_redirect' => $redirected->has($log->path),
            ]),
            'filters' => ['q' => $search, 'ignored' => $ignored],
        ]);
    }

    /**
     * PATCH /admin/erori-404/{log} — ignore or un-ignore.
     */
    public function update(Request $request, NotFoundLog $log): RedirectResponse
    {
        $log->update($request->validate(['is_ignored' => ['required', 'boolean']]));

        return back();
    }

    public function destroy(NotFoundLog $log): RedirectResponse
    {
        $log->delete();

        return back();
    }

    /**
     * DELETE /admin/erori-404 — clear the ignored list.
     */
    public function clearIgnored(): RedirectResponse
    {
        NotFoundLog::query()->where('is_ignored', true)->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lista ignorată a fost golită.']);

        return back();
    }
}
