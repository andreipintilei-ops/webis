{{--
    Features block (App\Blocks\FeaturesBlock): cards in columns, divided by
    hairlines. Each card: title, text, an optional note (when it fits) and an
    optional link, held to the foot of the card so the links line up.

    The home page asks for its services layout via featuresLayout
    (blocks/partials/services); other pages keep the CMS column setting. Neither presentation requires icons.
--}}
@php
    $items = array_values(array_filter($data['items'] ?? [], fn ($item) => is_array($item) && ($item['title'] ?? '') !== ''));
    $columns = (int) ($data['columns'] ?? 3);
    $hasLink = fn ($item): bool => is_array($item['link'] ?? null) && ($item['link']['label'] ?? '') !== '' && ($item['link']['url'] ?? '') !== '';
@endphp

@if ($items !== [] && ($featuresLayout ?? 'columns') === 'services')
    @include('blocks.partials.services')
@elseif ($items !== [] && ($featuresLayout ?? 'columns') === 'services-plain')
    @include('blocks.partials.services-plain')
@elseif ($items !== [])
    <section id="{{ $block['id'] }}" class="px-[var(--site-gap)] py-24 md:py-36">
        <div class="mx-auto max-w-[var(--content-max)]">
            @if (! empty($data['heading']))
                <h2 class="font-display text-[clamp(2.5rem,5vw,4.5rem)] leading-[1.16] font-normal uppercase">{{ $data['heading'] }}</h2>
            @endif

            @if (! empty($data['intro']))
                <p class="mt-6 max-w-2xl text-lg text-pretty text-neutral-600">{{ $data['intro'] }}</p>
            @endif

            <ul @class([
                'mt-14 grid divide-y divide-neutral-200 border-y border-neutral-200 md:mt-20 md:divide-x md:divide-y-0',
                'md:grid-cols-2' => $columns === 2,
                'md:grid-cols-3' => $columns === 3,
                'md:grid-cols-2 lg:grid-cols-4' => $columns === 4,
            ])>
                @foreach ($items as $item)
                    {{-- Hairlines between cards: rows on phones, columns from md up. --}}
                    <li class="flex flex-col py-10 md:px-8 md:py-12 md:first:pl-0 md:last:pr-0 lg:px-10">
                        <h3 class="text-2xl leading-tight font-semibold tracking-[-0.01em] text-balance">{{ $item['title'] }}</h3>

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
