<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Project;
use App\Models\SlugHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    /**
     * GET /proiect/{slug} — one published client project: what it was, what
     * the client needed, what we built and with what, its images. At the old
     * site's addresses; a slug the project used to have answers with a 301.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $project = Project::query()
            ->published()
            ->where('slug', $slug)
            ->with(['client:id,name', 'cover.media'])
            ->first();

        if ($project === null) {
            $renamed = SlugHistory::resolve(Project::class, $slug);

            abort_unless($renamed instanceof Project && $renamed->isPublished(), 404);

            return redirect()->route('projects.show', $renamed->slug, 301);
        }

        // The gallery, in the order it was set; images since deleted drop out.
        $ids = array_map(intval(...), $project->images ?? []);
        $images = Asset::query()->with('media')->whereKey($ids)->get()
            ->sortBy(fn (Asset $asset) => array_search($asset->id, $ids, true))
            ->values();

        return view('public.project', [
            'project' => $project,
            'images' => $images,
        ]);
    }
}
