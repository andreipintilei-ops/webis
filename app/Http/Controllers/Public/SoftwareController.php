<?php

namespace App\Http\Controllers\Public;

use App\Enums\ProjectType;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Contracts\View\View;

class SoftwareController extends Controller
{
    /**
     * GET /software-la-comanda/ — the "Software la comandă" service page
     * (public/software.blade.php). Its copy is the page's own; only its
     * projects come from the portfolio: up to three published client
     * projects of the type "software", none at all when there are none.
     */
    public function __invoke(): View
    {
        $projects = Project::query()
            ->published()
            ->where('type', ProjectType::Software)
            ->with(['client:id,name', 'cover.media'])
            ->orderBy('sort_order')
            ->orderByDesc('year')
            ->latest('id')
            ->limit(3)
            ->get();

        return view('public.software', ['projects' => $projects]);
    }
}
