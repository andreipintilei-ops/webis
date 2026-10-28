<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\NotFoundLog;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Support\AdminLinks;
use App\Support\AdminTime;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * GET /admin — what needs attention: new leads, what goes live next,
     * unfinished drafts and (for admins) the most frequent 404s.
     */
    public function __invoke(Request $request): Response
    {
        $contentModels = ['pages' => Page::class, 'projects' => Project::class, 'posts' => Post::class];

        $published = [];
        $drafts = 0;
        $upcoming = collect();

        foreach ($contentModels as $key => $model) {
            $published[$key] = $model::query()->published()->count();
            $drafts += $model::query()->where('status', ContentStatus::Draft->value)->count();
            $upcoming = $upcoming->concat($model::query()
                ->where('status', ContentStatus::Scheduled->value)
                ->orderBy('published_at')
                ->limit(5)
                ->get());
        }

        $scheduled = $upcoming
            ->sortBy('published_at')
            ->take(5)
            ->map(fn (Page|Project|Post $item): array => [
                ...AdminLinks::describe($item),
                'published_at' => AdminTime::display($item->published_at),
            ])
            ->values()
            ->all();

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'newLeads' => Lead::query()->where('status', LeadStatus::New->value)->count(),
                'leadsThisMonth' => Lead::query()
                    ->where('status', '!=', LeadStatus::Spam->value)
                    ->where('created_at', '>=', now(AdminTime::ZONE)->startOfMonth()->utc())
                    ->count(),
                'drafts' => $drafts,
                'published' => $published,
            ],
            'recentLeads' => Lead::query()
                ->where('status', '!=', LeadStatus::Spam->value)
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (Lead $lead): array => [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'service' => $lead->service_label,
                    'status' => $lead->status->value,
                    'status_label' => $lead->status->label(),
                    'created_at' => AdminTime::display($lead->created_at),
                ])
                ->values()
                ->all(),
            'scheduled' => $scheduled,
            'topNotFound' => $request->user()?->isAdmin()
                ? NotFoundLog::query()
                    ->where('is_ignored', false)
                    ->orderByDesc('hits')
                    ->limit(5)
                    ->get(['id', 'path', 'hits'])
                    ->map(fn (NotFoundLog $log): array => ['id' => $log->id, 'path' => $log->path, 'hits' => $log->hits])
                    ->values()
                    ->all()
                : null,
        ]);
    }
}
