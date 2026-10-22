{{--
    Stats block (App\Blocks\StatsBlock): big numbers with a label — "250+
    site-uri livrate" — as the page's band of emphasis: on the hero's flat
    indigo, as /web's steps (03), under the numbered section heading when
    there is one; css/site/block-stats.css.

    Each figure stands on a hairline: the number in the display face, its
    label, an optional note in the muted white. Two to a row on phones,
    up to three from 1024px (four when there are four).
--}}
@php
    $items = array_values(array_filter($data['items'] ?? [], fn ($item) => is_array($item) && trim((string) ($item['value'] ?? '')) !== ''));
    $heading = trim((string) ($data['heading'] ?? ''));
@endphp

@if ($items !== [])
    <section id="{{ $block['id'] }}" class="chapter chapter--dark block-stats" @if ($heading !== '') aria-labelledby="{{ $block['id'] }}-title" @endif data-nav-tone="dark" data-reveal-pace="fast">
        <div class="chapter__inner">
            @if ($heading !== '')
                @php $number = isset($sectionNumber) ? $sectionNumber() : ''; @endphp
                <x-ui.section-head :number="$number" :title="$heading" :id="$block['id'].'-title'" tone="dark" />
            @endif

            <dl class="block-stats__list block-stats__list--{{ count($items) }}" data-reveal>
                @foreach ($items as $item)
                    <div class="block-stats__item" data-reveal-child>
                        <dt class="block-stats__label">{{ $item['label'] }}</dt>
                        <dd class="block-stats__value">{{ $item['value'] }}</dd>
                        @if (! empty($item['note']))
                            <dd class="block-stats__note">{{ $item['note'] }}</dd>
                        @endif
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
@endif
