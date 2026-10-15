<?php

namespace App\Http\Controllers\Public;

use App\Enums\ProjectType;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    /**
     * GET /portofoliu — the client work (the menu's "Clienți"): up to three
     * featured projects, then the published projects as a list, all or one
     * type (?tip=magazin), and the clients' logos. Only client work: Webis's
     * own products are on Produse SaaS.
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

        $logos = Client::query()
            ->with('logo.media')
            ->where('show_in_logos', true)
            ->whereNotNull('logo_asset_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('public.clienti', [
            'type' => $type,
            'featured' => $featured,
            'projects' => $projects,
            'logos' => $logos,
        ]);
    }
}
