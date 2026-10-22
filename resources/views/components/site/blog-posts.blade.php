{{--
    The latest articles, on the home page after the reviews (as on
    integratedbio.com, without the featured one): a head (the title, the
    link to /blog), then three text cards — white, the hero indigo, the
    violet — each with its category and date, its title, and an arrow
    button (a rounded square) sitting in a notch cut from its corner. On
    hover the card grows behind its content, the title is underlined and the
    arrow leaves to the right as another comes in from the left.
    css/site/blog-posts.css.

    The three latest published articles (pages of type Article, as /blog
    lists them), each linking to its root address. Nothing at all until
    there are any.

    `title` (slot, optional): the heading, when a page wants its own wording
    instead of "Din experiența noastră." (/produse does).
--}}
@php
    $posts = \App\Models\Page::query()
        ->ofType(\App\Enums\PageType::Article)
        ->published()
        ->with('category:id,name')
        ->orderByDesc('published_at')
        ->orderByDesc('id')
        ->take(3)
        ->get()
        ->map(fn (\App\Models\Page $article) => [
            'title' => $article->title,
            'category' => $article->category?->name,
            'date' => $article->published_at,
            'href' => \App\Support\PublicUrls::for($article),
        ]);
@endphp

@if ($posts->isNotEmpty())
    <section id="blog" class="site-blog chapter chapter--light" aria-labelledby="blog-title">
        <div class="chapter__inner">
            <header class="site-blog__head" data-reveal="head">
                {{-- A page can give its own wording through the `title` slot. --}}
                <h2 id="blog-title" class="chapter__title">@isset($title){{ $title }}@else Din <span class="whitespace-nowrap">experiența <x-ui.title-pill variant="blog" /></span> <span class="whitespace-nowrap">noastră.</span>@endisset</h2>
                <x-ui.arrow-link :href="route('posts.index')">Toate articolele</x-ui.arrow-link>
            </header>

            <ul class="site-blog__posts" data-reveal>
                @foreach ($posts as $post)
                    <x-site.post-card :post="$post" :index="$loop->index" />
                @endforeach
            </ul>
        </div>
    </section>
@endif
