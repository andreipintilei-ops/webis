<?php

namespace App\Http\Controllers\Public;

use App\Enums\PageType;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * GET / — the CMS page of type "home". Plain Blade, no Inertia: public
     * pages ship complete HTML for crawlers.
     */
    public function __invoke(): View
    {
        return view('public.home', [
            'page' => Page::query()->ofType(PageType::Home)->published()->first(),
        ]);
    }
}
