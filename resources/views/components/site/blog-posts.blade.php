{{--
    The latest articles, on the home page after the reviews (as on
    integratedbio.com, without the featured one): a head (the title, the
    link to /blog), then three text cards — white, the hero indigo, the
    violet — each with its category and date, its title, and an arrow
    button (a rounded square) sitting in a notch cut from its corner. On
    hover the card grows behind its content, the title is underlined and the
    arrow leaves to the right as another comes in from the left.
    css/site/blog-posts.css.

    The three latest published posts; until there are any, three examples
    (DUMMY DATA).
--}}
@php
    $posts = \App\Models\Post::query()
        ->published()
        ->with('category')
        ->latest('published_at')
        ->take(3)
        ->get()
        ->map(fn (\App\Models\Post $post) => [
            'title' => $post->title,
            'category' => $post->category?->name,
            'date' => $post->published_at,
            // TODO: route('posts.show', $post) once the article page exists.
            'href' => url('/blog/'.$post->slug),
        ]);

    $dummy = $posts->isEmpty();

    if ($dummy) {
        $posts = collect([
            ['title' => 'ERP gata făcut sau construit de la zero: cum alegi', 'category' => 'Ghiduri', 'date' => '2026-09-22'],
            ['title' => 'Ce înseamnă, concret, digitalizarea unei registraturi', 'category' => 'Digitalizare', 'date' => '2026-09-08'],
            ['title' => 'Cinci lucruri de verificat înainte să lansezi un magazin online', 'category' => 'E-commerce', 'date' => '2026-08-25'],
        ])->map(fn (array $post) => [...$post, 'date' => \Carbon\CarbonImmutable::parse($post['date']), 'href' => route('posts.index')]);
    }

    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<section id="blog" class="site-blog chapter chapter--light" aria-labelledby="blog-title">
    <div class="chapter__inner">
        <header class="site-blog__head" data-reveal="head">
            <h2 id="blog-title" class="chapter__title">Din <span class="whitespace-nowrap">experiența <x-ui.title-pill variant="blog" /></span> <span class="whitespace-nowrap">noastră.</span></h2>
            <x-ui.arrow-link :href="route('posts.index')">Toate articolele</x-ui.arrow-link>
        </header>

        @if ($dummy)
            <!-- DUMMY DATA: example articles, until the first posts are published. -->
        @endif
        <ul class="site-blog__posts" data-reveal>
            @foreach ($posts as $post)
                <li style="--i: {{ $loop->index }}" data-reveal-child>
                    <a href="{{ $post['href'] }}" class="post-card">
                        {{-- The ground with its notch, and the button in it: one frame, which grows on hover. --}}
                        <span class="post-card__frame" aria-hidden="true">
                            <span class="post-card__bg"></span>
                            <span class="post-card__button"><span class="post-card__arrow">{!! $arrow !!}</span><span class="post-card__arrow post-card__arrow--next">{!! $arrow !!}</span></span>
                        </span>
                        <span class="post-card__meta">
                            @if ($post['category'])
                                <span class="post-card__category">{{ $post['category'] }}</span>
                            @endif
                            @if ($post['date'])
                                <time datetime="{{ $post['date']->toDateString() }}">{{ $post['date']->locale('ro')->translatedFormat('j F Y') }}</time>
                            @endif
                        </span>
                        <span class="post-card__title"><span>{{ $post['title'] }}</span></span>
                        <span class="post-card__foot">Citește articolul</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
