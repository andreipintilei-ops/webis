<?php

namespace App\Http\Controllers\Public;

use App\Enums\PageType;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * GET / — the CMS page of type "home", with the stage after its blocks
     * (public/home.blade.php). Plain Blade, no Inertia: public pages ship
     * complete HTML for crawlers.
     */
    public function __invoke(): View
    {
        return view('public.home', [
            'page' => Page::query()->ofType(PageType::Home)->published()->first(),
        ]);
    }

    /**
     * GET /poveste — a design trial kept for later: a copy of the home page,
     * from its own template (public/poveste.blade.php) and marked
     * data-variant="poveste" (css/site/variant-poveste.css).
     */
    public function poveste(): View
    {
        return view('public.poveste', [
            'page' => Page::query()->ofType(PageType::Home)->published()->first(),
        ]);
    }
}
