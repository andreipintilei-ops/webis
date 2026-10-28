{{--
    Stats block (App\Blocks\StatsBlock): big numbers with a label — "250+
    site-uri livrate" — as the page's band of emphasis: on the hero's flat
    indigo, as /web's steps (03), under the numbered section heading when
    there is one; css/site/block-stats.css.

    Each figure stands on a hairline: the number in the display face, its
    label, an optional note in the muted white. A share ("78%") fills its
    hairline that far, in lavender, so a set of them reads as a comparison.
    Two to a row on phones, up to three from 1024px (four or five when there
    are that many).

    The figures count up from 0 as they come into view, a share's hairline
    filling with them (js/public/stat-counters.ts).

    `body`: an optional passage under the figures, in the same section,
    as rich text (components/rich-text), in two columns from 1024px.
    `tone`: `light` sets the whole section on the light chapters' lavender
    instead.
--}}
@php
    $items = array_values(array_filter($data['items'] ?? [], fn ($item) => is_array($item) && trim((string) ($item['value'] ?? '')) !== ''));
    $heading = trim((string) ($data['heading'] ?? ''));
    $body = trim((string) ($data['body_html'] ?? ''));
    $dark = ($data['tone'] ?? 'dark') !== 'light';
@endphp

@if ($items !== [])
    <section id="{{ $block['id'] }}" class="chapter {{ $dark ? 'chapter--dark' : 'chapter--light' }} block-stats" @if ($heading !== '') aria-labelledby="{{ $block['id'] }}-title" @endif @if ($dark) data-nav-tone="dark" @endif data-reveal-pace="fast">
        <div class="chapter__inner">
            @if ($heading !== '')
                <x-ui.section-head :title="$heading" :id="$block['id'].'-title'" :tone="$dark ? 'dark' : 'light'" />
            @endif

            <dl class="block-stats__list block-stats__list--{{ count($items) }}" data-reveal>
                @foreach ($items as $item)
                    @php preg_match('/^(\d{1,3}(?:[.,]\d+)?)\s*%$/', trim($item['value']), $share); @endphp
                    <div @class(['block-stats__item', 'block-stats__item--share' => $share !== []]) @if ($share !== []) style="--share: {{ min(100, (float) str_replace(',', '.', $share[1])) }}%" @endif data-reveal-child>
                        <dt class="block-stats__label">{{ $item['label'] }}</dt>
                        <dd class="block-stats__value" data-count-up>{{ $item['value'] }}</dd>
                        @if (! empty($item['note']))
                            <dd class="block-stats__note">{{ $item['note'] }}</dd>
                        @endif
                    </div>
                @endforeach
            </dl>

            @if ($body !== '')
                <x-rich-text :html="$body" class="block-stats__body" data-reveal />
            @endif
        </div>
    </section>
@endif
