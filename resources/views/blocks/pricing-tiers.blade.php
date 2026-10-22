{{--
    Pricing block (App\Blocks\PricingTiersBlock): up to four packages side by
    side, as /web's offers (01): the site's notched card (css/site/
    blog-posts.css) on white, the highlighted package on the hero's indigo
    with a "Recomandat" chip. Under the numbered section heading, on the
    light chapter's lavender; css/site/block-pricing-tiers.css.

    Each card: the name (a mono label), the price in the display face and
    its period, a sentence, the features with the violet's ticks. A package
    with a button is a link as a whole — the arrow in the notch, the
    button's label at the foot; without one the card has no notch.

    Prices are free text ("de la 2.500 lei"). The note (VAT and the like)
    closes the section.
--}}
@php
    $tiers = array_values(array_filter($data['tiers'] ?? [], fn ($tier) => is_array($tier) && trim((string) ($tier['name'] ?? '')) !== ''));
    $heading = trim((string) ($data['heading'] ?? ''));
    $intro = trim((string) ($data['intro'] ?? ''));
    $note = trim((string) ($data['note'] ?? ''));
    $linkOf = fn (array $tier): ?array => is_array($tier['cta'] ?? null) && ($tier['cta']['label'] ?? '') !== '' && ($tier['cta']['url'] ?? '') !== '' ? $tier['cta'] : null;

    $check = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

@if ($tiers !== [])
    <section id="{{ $block['id'] }}" class="chapter chapter--light block-pricing" @if ($heading !== '') aria-labelledby="{{ $block['id'] }}-title" @endif data-reveal-pace="fast">
        <div class="chapter__inner">
            @if ($heading !== '')
                @php $number = isset($sectionNumber) ? $sectionNumber() : ''; @endphp
                <x-ui.section-head :number="$number" :title="$heading" :id="$block['id'].'-title'" :intro="$intro ?: null" />
            @elseif ($intro !== '')
                <p class="block-pricing__intro">{{ $intro }}</p>
            @endif

            <ul class="block-pricing__list block-pricing__list--{{ count($tiers) }}" data-reveal>
                @foreach ($tiers as $tier)
                    @php
                        $link = $linkOf($tier);
                        $tag = $link ? 'a' : 'div';
                        $features = array_values(array_filter((array) ($tier['features'] ?? []), fn ($feature) => trim((string) $feature) !== ''));
                    @endphp
                    <li data-reveal-child>
                        <{{ $tag }} @if ($link) href="{{ $link['url'] }}" @endif @class([
                            'post-card block-tier',
                            'block-tier--featured' => ! empty($tier['highlighted']),
                            'block-tier--plain' => ! $link,
                        ])>
                            <span class="post-card__frame" aria-hidden="true">
                                <span class="post-card__bg"></span>
                                @if ($link)
                                    <span class="post-card__button"><span class="post-card__arrow">{!! $arrow !!}</span><span class="post-card__arrow post-card__arrow--next">{!! $arrow !!}</span></span>
                                @endif
                            </span>

                            <div class="block-tier__head">
                                <h3 class="block-tier__name">{{ $tier['name'] }}</h3>
                                @if (! empty($tier['highlighted']))
                                    <span class="post-card__category">Recomandat</span>
                                @endif
                            </div>

                            <p class="block-tier__price">
                                <span class="block-tier__amount">{{ $tier['price'] }}</span>
                                @if (! empty($tier['period']))
                                    <span class="block-tier__period">{{ $tier['period'] }}</span>
                                @endif
                            </p>

                            @if (! empty($tier['description']))
                                <p class="block-tier__description">{{ $tier['description'] }}</p>
                            @endif

                            @if ($features !== [])
                                <ul class="block-tier__features">
                                    @foreach ($features as $feature)
                                        <li>{!! $check !!}<span>{{ $feature }}</span></li>
                                    @endforeach
                                </ul>
                            @endif

                            @if ($link)
                                <span class="post-card__foot">{{ $link['label'] }}</span>
                            @endif
                        </{{ $tag }}>
                    </li>
                @endforeach
            </ul>

            @if ($note !== '')
                <p class="block-pricing__note">{{ $note }}</p>
            @endif
        </div>
    </section>
@endif
