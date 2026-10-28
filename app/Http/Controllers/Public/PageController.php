<?php

namespace App\Http\Controllers\Public;

use App\Enums\PageType;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SlugHistory;
use App\Support\PublicUrls;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    /**
     * GET /{slug} — a published CMS page at the site root (services, contact,
     * legal, articles…). A slug the page used to have answers with a 301 to
     * its current address, so renamed pages keep their rankings; so does a
     * page that lives elsewhere (the home page at "/", an industry page under
     * /servicii/).
     */
    public function show(string $slug): View|RedirectResponse
    {
        $page = $this->find($slug);

        if (! $page instanceof Page) {
            return $page;
        }

        if (in_array($page->type, [PageType::Home, PageType::Industry], true)) {
            return redirect(PublicUrls::for($page), 301);
        }

        return view($page->type === PageType::Contact ? 'public.contact' : 'public.page', ['page' => $page]);
    }

    /**
     * GET /servicii/{slug} — an industry page, at the old site's address. Any
     * other page asked for here goes to its own address with a 301, as an
     * industry page asked for at the root comes here (show()): each has one.
     */
    public function industry(string $slug): View|RedirectResponse
    {
        $page = $this->find($slug);

        if (! $page instanceof Page) {
            return $page;
        }

        if ($page->type !== PageType::Industry) {
            return redirect(PublicUrls::for($page), 301);
        }

        return view('public.page', ['page' => $page]);
    }

    /**
     * The published page at the slug, or a 301 to a renamed one's current
     * address; 404 when there is neither.
     */
    private function find(string $slug): Page|RedirectResponse
    {
        $page = Page::query()->published()->where('slug', $slug)->first();

        if ($page !== null) {
            return $page;
        }

        $renamed = SlugHistory::resolve(Page::class, $slug);

        abort_unless($renamed instanceof Page && $renamed->isPublished(), 404);

        return redirect(PublicUrls::for($renamed), 301);
    }
}
