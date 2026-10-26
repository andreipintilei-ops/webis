{{--
    Features block (App\Blocks\FeaturesBlock): cards in columns, divided by
    hairlines, over as many rows as they need (css/site/block-features.css).
    Each card: title, after a check in the violet (as the pricing cards'
    features), text, an optional note (when it fits) and an optional link,
    held to the foot of the card so the links line up. A card with a title
    alone (a list of what is included) sets it smaller still.

    The layout: a page template's featuresLayout (home: blocks/partials/
    services; /clienti: services-plain), else the block's own `layout` —
    "columns" (below) or "cards" (blocks/partials/service-cards). None
    requires icons.
--}}
@php
    $items = array_values(array_filter($data['items'] ?? [], fn ($item) => is_array($item) && ($item['title'] ?? '') !== ''));
    $columns = (int) ($data['columns'] ?? 3);
    $hasLink = fn ($item): bool => is_array($item['link'] ?? null) && ($item['link']['label'] ?? '') !== '' && ($item['link']['url'] ?? '') !== '';

    // The page template's choice (home, /clienti), else the block's own.
    $layout = $featuresLayout ?? ($data['layout'] ?? 'columns');
@endphp

@if ($items !== [] && $layout === 'services')
    @include('blocks.partials.services')
@elseif ($items !== [] && $layout === 'services-plain')
    @include('blocks.partials.services-plain')
@elseif ($items !== [] && $layout === 'cards')
    @include('blocks.partials.service-cards')
@elseif ($items !== [])
    <section id="{{ $block['id'] }}" class="block-features px-[var(--site-gap)] py-24 md:py-36" @if (! empty($data['heading'])) aria-labelledby="{{ $block['id'] }}-title" @endif>
        <div class="mx-auto max-w-[var(--content-max)]">
            @if (! empty($data['heading']))
                <x-ui.section-head :title="$data['heading']" :id="$block['id'].'-title'" :intro="$data['intro'] ?? null" />
            @elseif (! empty($data['intro']))
                <p class="mt-6 max-w-2xl text-lg text-pretty text-neutral-600">{{ $data['intro'] }}</p>
            @endif

            <ul @class([
                'feature-grid feature-grid--'.$columns.' mt-14 grid border-y border-neutral-200 md:mt-20',
                'md:grid-cols-2' => $columns === 2,
                'md:grid-cols-3' => $columns === 3,
                'md:grid-cols-2 lg:grid-cols-4' => $columns === 4,
            ])>
                @foreach ($items as $item)
                    <li @class(['flex flex-col', 'feature-grid__item--plain' => empty($item['text'])])>
                        <h3 class="feature-grid__title">
                            <span class="feature-grid__check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></span>
                            <span>{{ $item['title'] }}</span>
                        </h3>

                        @if (! empty($item['text']))
                            <p class="mt-5 text-pretty text-neutral-600">{{ $item['text'] }}</p>
                        @endif

                        @if (! empty($item['note']))
                            <p class="mt-5 font-medium text-pretty">{{ $item['note'] }}</p>
                        @endif

                        @if ($hasLink($item))
                            <div class="mt-auto pt-10">
                                <x-ui.arrow-link :href="$item['link']['url']">{{ $item['link']['label'] }}</x-ui.arrow-link>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
