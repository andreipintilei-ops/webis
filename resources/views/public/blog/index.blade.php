{{--
    /blog and /blog/categorie/{slug} (Public\ArticleController) — the
    listing: the internal pages' hero (components/site/page-hero), then the
    articles as the home page's text cards (components/site/post-card), nine
    to a page (components/site/pagination, ?pagina=2). Each card links to the
    article at its root address. css/site/blog-posts.css.

    `category`: the PostCategory listed, or null for all. `articles`: a
    LengthAwarePaginator of cards. `categories`: those with articles, each
    ['name', 'slug', 'count', 'href'], most first. `total`: every published
    article.
--}}
@php
    $page = $articles->currentPage();

    // Each page of the listing names itself, as Google advises for a series
    // of pages; the first without ?pagina (null: the layout's own default).
    $canonical = $page > 1 ? \App\Support\PublicUrls::canonical(request()->getPathInfo()).'?pagina='.$page : null;

    $title = $category ? ($category->seo->title ?: $category->name.' · Blog') : 'Blog';
    $description = $category?->seo->description ?: $category?->description
        ?: 'Articole despre software la comandă, produse SaaS, site-uri și magazine online, scrise de echipa Webis din Iași.';
@endphp

<x-layouts.public :title="$page > 1 ? $title.', pagina '.$page : $title" :crumb="$category?->name ?? 'Blog'" :description="$description" :canonical="$canonical" :noindex="(bool) $category?->seo->noindex" header-theme="dark" :footer-w="true">
    <x-site.page-hero
        :label="$category ? 'Blog · '.$category->name : 'Blog · Webis'"
        :title="['Articole despre', 'software și web.']"
        :intro="$category?->description ?: 'Ghiduri și noutăți despre software la comandă, site-uri, magazine online și SEO.'"
    />

    <section id="articole" class="site-blog site-blog--page chapter chapter--light" aria-label="Articole">
        <div class="chapter__inner">
            <div class="site-blog__layout">
                {{-- The categories: a column at the right from 1024px, a row of
                     chips above the articles below it; each its own address.
                     None until there are articles. --}}
                @if ($total > 0)
                    <aside class="blog-categories" aria-labelledby="blog-categories-title">
                        <h2 id="blog-categories-title" class="blog-categories__title">Categorii</h2>
                        <ul class="blog-categories__list">
                            <li>
                                <a href="{{ route('posts.index') }}#articole" class="blog-categories__link"@if (! $category) aria-current="page"@endif data-no-transition>
                                    <span>Toate articolele</span><span class="blog-categories__count">{{ $total }}</span>
                                </a>
                            </li>
                            @foreach ($categories as $item)
                                <li>
                                    <a href="{{ $item['href'] }}#articole" class="blog-categories__link"@if ($category && $category->slug === $item['slug']) aria-current="page"@endif data-no-transition>
                                        <span>{{ $item['name'] }}</span><span class="blog-categories__count">{{ $item['count'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </aside>
                @endif

                <div class="site-blog__main">
                    @if ($articles->isEmpty())
                        <p class="portfolio-empty">{{ $category ? 'Încă nu am publicat articole în această categorie.' : 'Încă nu am publicat articole.' }}</p>
                    @else
                        {{-- One grid, so a row is never left half empty however
                             many columns fit: three beside the categories only
                             from 1280px, two below. Each card comes in as it is
                             reached; all white here (the home page's colour cycle
                             is for its three alone). --}}
                        <ul class="site-blog__posts">
                            @foreach ($articles->getCollection() as $post)
                                <x-site.post-card :post="$post" :index="$loop->index % 3" :alone="true" sizes="(min-width: 1280px) 22vw, (min-width: 1024px) 32vw, (min-width: 640px) 46vw, 92vw" />
                            @endforeach
                        </ul>

                        <x-site.pagination :paginator="$articles" label="Paginile blogului" />
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
