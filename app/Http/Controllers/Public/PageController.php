<?php

namespace App\Http\Controllers\Public;

use App\Enums\PageType;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SlugHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    /**
     * GET /{slug} — a published CMS page at the site root (services, industries,
     * contact, legal…). A slug the page used to have answers with a 301 to its
     * current address, so renamed pages keep their rankings.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $page = Page::query()->published()->where('slug', $slug)->first();

        if ($page === null) {
            $renamed = SlugHistory::resolve(Page::class, $slug);

            abort_unless($renamed instanceof Page && $renamed->isPublished(), 404);

            return redirect($renamed->type === PageType::Home ? '/' : '/'.$renamed->slug, 301);
        }

        // The home page lives at "/", never at its own slug.
        if ($page->type === PageType::Home) {
            return redirect('/', 301);
        }

        return view('public.page', ['page' => $page]);
    }
}
