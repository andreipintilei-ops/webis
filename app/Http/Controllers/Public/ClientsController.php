<?php

namespace App\Http\Controllers\Public;

use App\Enums\ProjectType;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    /**
     * GET /portofoliu — the client work (the menu's "Clienți"): up to three
     * featured projects, then the published projects as a list, all or one
     * type (?tip=magazin). Only client work: Webis's own products are on
     * Produse SaaS. The clients' logos load their own (x-logo-marquee).
     *
     * Its CMS page is the one at the slug "portofoliu" ("Clienți" in the
     * admin): the page's own blocks go under the hero, and its SEO title and
     * description stand in for the ones written here.
     */
    public function __invoke(Request $request): View
    {
        // The filter in effect. Every project is sent: the list hides the
        // others, and filters in place from there (js/public/work-list.ts).
        // An unknown ?tip= shows them all.
        $type = ProjectType::tryFrom((string) $request->query('tip', ''));

        $projects = Project::query()
            ->published()
            ->whereNotNull('type')
            ->with(['client:id,name', 'cover.media'])
            ->orderBy('sort_order')
            ->orderByDesc('year')
            ->latest('id')
            ->get();

        // The featured ones lead the page, whatever the filter.
        $featured = Project::query()
            ->published()
            ->whereNotNull('type')
            ->where('is_featured', true)
            ->with(['client:id,name', 'cover.media'])
            ->orderBy('sort_order')
            ->latest('id')
            ->limit(3)
            ->get();

        return view('public.clienti', [
            'page' => Page::query()->where('slug', 'portofoliu')->published()->first(),
            'type' => $type,
            'featured' => $featured,
            'projects' => $projects,
        ]);
    }
}
