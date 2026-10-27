<?php

namespace App\Http\Controllers\Public;

use App\Enums\PageType;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PostCategory;
use App\Models\SlugHistory;
use App\Support\Blog\Cover;
use App\Support\PublicUrls;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The blog's listings: the published articles — pages of type Article, each
 * at its own root address (/{slug}) — newest first, nine to a page
 * (?pagina=2). Never the Post model: nothing fills it.
 * public/blog/index.blade.php.
 */
class ArticleController extends Controller
{
    private const int PER_PAGE = 9;

    /**
     * GET /blog — every article, as cards. A page past the last is not
     * found.
     */
    public function index(): View
    {
        $articles = $this->published($this->articles())
            ->paginate(self::PER_PAGE, pageName: 'pagina')
            ->withPath(route('posts.index'));

        abort_if($articles->currentPage() > $articles->lastPage(), 404);

        return $this->listing($articles);
    }

    /**
     * GET /blog/categorie/{slug} — one category's articles, at its own
     * indexable address. A slug the category used to have answers with a
     * 301 to its current one.
     */
    public function category(string $slug): View|RedirectResponse
    {
        $category = PostCategory::query()->where('slug', $slug)->first();

        if ($category === null) {
            $renamed = SlugHistory::resolve(PostCategory::class, $slug);

            abort_unless($renamed instanceof PostCategory, 404);

            return redirect(route('posts.category', $renamed->slug), 301);
        }

        $articles = $this->published($category->pages())
            ->paginate(self::PER_PAGE, pageName: 'pagina')
            ->withPath(route('posts.category', $category->slug));

        abort_if($articles->currentPage() > $articles->lastPage(), 404);

        return $this->listing($articles, $category);
    }

    /**
     * Every article, as a query.
     *
     * @return Builder<Page>
     */
    private function articles(): Builder
    {
        return Page::query()->ofType(PageType::Article);
    }

    /**
     * The published ones among $query's articles, newest first, with their
     * category.
     *
     * @param  Builder<Page>|HasMany<Page, PostCategory>  $query
     * @return Builder<Page>|HasMany<Page, PostCategory>
     */
    private function published(Builder|HasMany $query): Builder|HasMany
    {
        return $query
            ->published()
            ->with(['category:id,name,slug', 'media'])
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /**
     * @param  LengthAwarePaginator<int, Page>  $articles
     */
    private function listing(LengthAwarePaginator $articles, ?PostCategory $category = null): View
    {
        return view('public.blog.index', [
            'category' => $category,
            'articles' => $articles->through(fn (Page $article): array => $this->card($article)),
            'categories' => $this->categories(),
            'total' => $this->articles()->published()->count(),
        ]);
    }

    /**
     * The categories that have articles, most first, for the sidebar.
     *
     * @return Collection<int, array{name: string, slug: string, count: int, href: string}>
     */
    private function categories(): Collection
    {
        // Published articles per category id, in one query.
        $counts = $this->articles()
            ->published()
            ->whereNotNull('post_category_id')
            ->groupBy('post_category_id')
            ->selectRaw('post_category_id, count(*) as articles')
            ->pluck('articles', 'post_category_id');

        return PostCategory::query()
            ->whereKey($counts->keys()->all())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (PostCategory $category): array => [
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => (int) $counts->get($category->id),
                'href' => route('posts.category', $category->slug),
            ])
            ->sortByDesc('count')
            ->values();
    }

    /**
     * An article as components/site/post-card takes it.
     *
     * @return array{href: string, title: string, category: string|null, date: CarbonInterface|null, cover: Cover|null}
     */
    private function card(Page $article): array
    {
        return [
            'href' => PublicUrls::for($article),
            'title' => $article->title,
            'category' => $article->category?->name,
            'date' => $article->published_at,
            'cover' => Cover::of($article),
        ];
    }
}
