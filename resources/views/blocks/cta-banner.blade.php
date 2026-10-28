{{--
    CTA block (App\Blocks\CtaBannerBlock): a call to action on its own panel,
    in the light chapter's lavender — the closing panel's look (components/
    site/cta-panel) without its W, so it can stand anywhere on a page;
    css/site/block-cta-banner.css.

    `variant`: "default" — the hero's indigo, lit by its purple and violet
    glows; "accent" — the violet (the site keeps it to at most one call to
    action a page). Either way white: the title in the display face, a
    line, the white pill and, beside it, an arrow link.

    From 1024px the buttons stand at the right, on the text's last line.
--}}
@php
    $heading = trim((string) ($data['heading'] ?? ''));
    $isLink = fn ($cta): bool => is_array($cta) && ($cta['label'] ?? '') !== '' && ($cta['url'] ?? '') !== '';
    $primary = $isLink($data['primary_cta'] ?? null) ? $data['primary_cta'] : null;
    $secondary = $isLink($data['secondary_cta'] ?? null) ? $data['secondary_cta'] : null;
    $accent = ($data['variant'] ?? 'default') === 'accent';
@endphp

@if ($heading !== '')
    <section id="{{ $block['id'] }}" class="chapter chapter--light block-cta" aria-labelledby="{{ $block['id'] }}-title">
        <div @class(['block-cta__panel', 'block-cta__panel--accent' => $accent]) data-nav-tone="dark" data-reveal="head">
            <div class="block-cta__text">
                <h2 id="{{ $block['id'] }}-title" class="block-cta__title">{{ $heading }}</h2>
                @if (! empty($data['text']))
                    <p class="block-cta__line">{{ $data['text'] }}</p>
                @endif
            </div>

            @if ($primary || $secondary)
                <div class="block-cta__actions">
                    @if ($primary)
                        <x-ui.pill :href="$primary['url']" tone="dark">{{ $primary['label'] }}</x-ui.pill>
                    @endif
                    @if ($secondary)
                        <x-ui.arrow-link :href="$secondary['url']" tone="dark">{{ $secondary['label'] }}</x-ui.arrow-link>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endif
