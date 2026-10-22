{{--
    The featured client projects as screen-high cards stacking on scroll, on
    the light lavender: each card in a colour of the hero's, the project's
    client large, what it was,
    one line and its site on the left, its screenshot on the right. From
    1024px each comes in a little smaller and tilted, settles, then holds
    while the next slides up over it (js/public/product-stack.ts). Below
    that, and with reduced motion, cards one after another.
    css/site/work-stack.css.

    `items`: list of ['href', 'client', 'type' (App\Enums\ProjectType),
    'line', 'site' (or null), 'image' (a URL, or null)].
--}}
@props(['items'])

<section id="proiecte-selectate" class="work-stack" aria-labelledby="featured-work-title" data-product-stack data-reveal-pace="fast">
    <header class="work-stack__head" data-reveal="head">
        <div class="md:col-span-7">
            <p class="chapter__label">Proiecte selectate</p>
            <h2 id="featured-work-title" class="chapter__title">Câteva dintre<br>proiectele noastre.</h2>
        </div>
        <p class="work-stack__intro">De la magazine online la software făcut pe măsură: ce aveau nevoie clienții și ce am construit.</p>
    </header>

    <div class="work-stack__cards">
        @foreach ($items as $item)
            <article class="work-stack__card work-stack__card--{{ $loop->iteration }}" style="--i: {{ $loop->index }}" aria-labelledby="featured-work-{{ $loop->iteration }}" data-product-card>
                {{-- The entry moves this frame; being covered moves the panel. --}}
                <div class="work-stack__frame" data-product-entry>
                    <div class="work-stack__panel" data-product-panel>
                        <div class="work-stack__content">
                            <p class="work-stack__count">{{ sprintf('%02d', $loop->iteration) }} / {{ sprintf('%02d', count($items)) }} · {{ $item['type']?->label() }}</p>
                            <h3 id="featured-work-{{ $loop->iteration }}" class="work-stack__client">{{ $item['client'] }}</h3>
                            @if ($item['line'])
                                <p class="work-stack__line">{{ $item['line'] }}</p>
                            @endif
                            @if ($item['site'])
                                <p class="work-stack__site">{{ $item['site'] }}</p>
                            @endif
                            {{-- The white button, on the card's colour. --}}
                            <x-ui.pill :href="$item['href']" tone="dark" class="work-stack__link">Vezi proiectul</x-ui.pill>
                        </div>

                        <a href="{{ $item['href'] }}" class="work-stack__visual" tabindex="-1" aria-hidden="true">
                            @if ($item['image'])
                                <img src="{{ $item['image'] }}" alt="" loading="lazy" decoding="async">
                            @endif
                        </a>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
