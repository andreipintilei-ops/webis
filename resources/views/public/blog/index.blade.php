{{--
    /blog and /blog/categorie/{slug} (Public\ArticleController) — the
    listing: the internal pages' hero (components/site/page-hero), then on
    /blog's first page the latest article across the hero's foot
    (css/site/page-hero.css), and the articles as the home page's text cards
    (components/site/post-card), nine to a page (components/site/pagination,
    ?pagina=2). Each card links to the article at its root address.
    css/site/blog-posts.css.

    `category`: the PostCategory listed, or null for all. `featured`: the
    lead article as a card with its `excerpt`, or null. `articles`: a
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

    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<x-layouts.public :title="$page > 1 ? $title.', pagina '.$page : $title" :crumb="$category?->name ?? 'Blog'" :description="$description" :canonical="$canonical" :noindex="(bool) $category?->seo->noindex" header-theme="dark">
    <x-site.page-hero
        :label="$category ? 'Blog · '.$category->name : 'Blog · Webis'"
        :title="['Articole despre', 'software și web.']"
        :intro="$category?->description ?: 'Ghiduri și noutăți despre software la comandă, site-uri, magazine online și SEO.'"
        :class="$featured ? 'page-hero--led' : null"
    >
        {{-- The featured article closes the hero, on a hairline across its
             foot, as the home page's rating and logos close its own. --}}
        @if ($featured)
            <x-slot:after>
                <a href="{{ $featured['href'] }}" class="blog-lead">
                    <span class="blog-lead__meta">
                        <span>Articol recomandat</span>
                        @if ($featured['category'])
                            <span aria-hidden="true">·</span>
                            <span>{{ $featured['category'] }}</span>
                        @endif
                        @if ($featured['date'])
                            <span aria-hidden="true">·</span>
                            <time datetime="{{ $featured['date']->toDateString() }}">{{ $featured['date']->locale('ro')->translatedFormat('j F Y') }}</time>
                        @endif
                    </span>
                    <span class="blog-lead__title"><span>{{ $featured['title'] }}</span></span>
                    <span class="blog-lead__side">
                        <span class="blog-lead__excerpt">{{ $featured['excerpt'] }}</span>
                        <span class="blog-lead__arrow" aria-hidden="true">{!! $arrow !!}</span>
                    </span>
                </a>
            </x-slot:after>
        @endif
    </x-site.page-hero>

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
                    @if ($articles->isEmpty() && ! $featured)
                        <p class="portfolio-empty">{{ $category ? 'Încă nu am publicat articole în această categorie.' : 'Încă nu am publicat articole.' }}</p>
                    @else
                        {{-- In rows of three, each coming in as it is reached; all white
                             here (the home page's colour cycle is for its three alone). --}}
                        <div class="site-blog__rows">
                            @foreach ($articles->getCollection()->chunk(3) as $row)
                                <ul class="site-blog__posts" data-reveal>
                                    @foreach ($row as $post)
                                        <x-site.post-card :post="$post" :index="$loop->index" />
                                    @endforeach
                                </ul>
                            @endforeach
                        </div>

                        <x-site.pagination :paginator="$articles" label="Paginile blogului" />
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
