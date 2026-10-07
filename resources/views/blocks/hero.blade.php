{{--
    Hero block (App\Blocks\HeroBlock): the page's single <h1>.

    - centered: full-screen, text centred (or, with align "left", at the
      left of the content column), optionally with the client-logo marquee along the
      bottom. Over a dark backdrop — the animated
      gradient or an image — it is white (and the header switches to its
      light-on-dark variant); without one it is dark on the page's white.
    - split: text beside the image on the page background.
--}}
@php
    $image = isset($data['image_asset_id']) ? $assets->get($data['image_asset_id']) : null;
    $backdrop = \App\Blocks\HeroBlock::backdrop($data);
    $palette = \App\Blocks\HeroBlock::palette($data);

    // An image that has since disappeared leaves a plain hero, not white on white.
    if ($backdrop === 'image' && $image === null) {
        $backdrop = 'none';
    }

    $dark = $backdrop !== 'none';
    $left = ($data['align'] ?? 'center') === 'left';
    $logos = (bool) ($data['logos'] ?? false);

    // Line breaks typed in the title are kept. With them the editor has chosen
    // the lines, so the width cap that otherwise shapes the wrap is dropped.
    $headingLines = preg_split('/\R/', trim((string) ($data['heading'] ?? ''))) ?: [''];
    $headingWidth = count($headingLines) > 1 ? 'max-w-none' : 'max-w-[20ch]';

    // Anton, uppercase, up to 100px. Anton has one weight: it must stay
    // font-normal, or the browser draws a fake bold. The line height leaves
    // room for Romanian capitals — Î and Ă carry marks above the cap height
    // that would otherwise touch the line before.
    $headingType = 'font-display text-[clamp(3rem,7vw,6.25rem)] leading-[1.16] font-normal uppercase text-balance';

    // The page's first, dark, full-screen hero: its backdrop is fixed behind
    // the page (blocks after it may sit on it too), rests as a card that opens
    // out on scroll, and plays the intro (js/public/hero-intro.ts). The
    // data-intro-* hooks do nothing on their own.
    $intro = $isFirst && $dark;
@endphp

@if ($intro)
    @push('backdrop')
        @include('blocks.partials.hero-backdrop', ['fixed' => true])
    @endpush
@endif

@if (($data['layout'] ?? 'split') === 'centered')
    <section id="{{ $block['id'] }}" @class([
        'relative isolate flex min-h-svh items-center overflow-hidden pt-32',
        // Room for the logo strip along the bottom.
        'pb-44 md:pb-52' => $logos,
        'pb-32' => ! $logos,
        // Left: the header's edge padding on small screens; on wide ones the
        // content column inside sets the text in from the logo.
        'px-[var(--site-gap)] text-left' => $left,
        'justify-center px-6 text-center md:px-12' => ! $left,
        'text-white' => $dark,
        'text-black' => ! $dark,
    ]) @if ($dark) data-nav-tone="dark" @endif @if ($isFirst) data-hero-parallax @if (! $intro) data-speed="clamp(0.5)" @endif @endif @if ($intro) data-intro data-backdrop-area @endif>
        {{--
            The opening hero: the page after it slides up over it
            (css/site/hero-parallax.css). With a fixed backdrop it scrolls at
            normal speed — the background holding still is the depth; a light
            hero lags at half speed instead (ScrollSmoother data-speed).
        --}}
        @if (! $intro && $backdrop !== 'none')
            @include('blocks.partials.hero-backdrop', ['fixed' => false])
        @endif

        {{-- Left: the content column (--content-max), narrower than the header. --}}
        <div @class(['mx-auto w-full', 'max-w-[var(--content-max)]' => $left, 'max-w-5xl' => ! $left])>
            @if (! empty($data['eyebrow']))
                <p @class(['mb-5 text-sm md:mb-6 md:text-base', 'text-white/85' => $dark, 'text-neutral-600' => ! $dark]) data-intro-fade>{{ $data['eyebrow'] }}</p>
            @endif

            {{-- One masked line per typed line, so each can rise into view. --}}
            <h1 @class([$headingWidth, $headingType, 'mx-auto' => ! $left])>
                @foreach ($headingLines as $line)
                    <span class="hero-line" data-intro-line><span class="hero-line__inner">{{ $line }}</span></span>
                @endforeach
            </h1>

            @if (! empty($data['subheading']))
                {{-- Left: about half the content column, as the text block in the design reference. --}}
                <p @class(['mt-6 text-lg text-pretty', 'max-w-xl' => $left, 'mx-auto max-w-2xl' => ! $left, 'text-white/85' => $dark, 'text-neutral-600' => ! $dark]) data-intro-fade>{{ $data['subheading'] }}</p>
            @endif

            <div data-intro-fade>
                @include('blocks.partials.ctas', [
                    'primary' => $data['primary_cta'] ?? null,
                    'secondary' => $data['secondary_cta'] ?? null,
                    'tone' => $dark ? 'dark' : 'light',
                    'class' => $left ? 'mt-10' : 'mt-10 justify-center',
                ])
            </div>
        </div>

        @if ($logos)
            {{-- In the content column, under a hairline that spans it, with the
                 same space above and below the logos (pt / pb) — so that
                 hairline and the one that may follow (a statement on the hero
                 background) sit evenly around them. --}}
            <div class="absolute inset-x-0 bottom-0 px-[var(--site-gap)] pb-8 md:pb-10" data-intro-fade>
                {{-- The Google rating above the strip, at the column's right edge. --}}
                <div class="mx-auto flex max-w-[var(--content-max)] justify-end pb-4 md:pb-5">
                    <x-site.google-rating :tone="$dark ? 'dark' : 'light'" />
                </div>
                <div @class(['mx-auto max-w-[var(--content-max)] border-t pt-8 md:pt-10', 'border-white/25' => $dark, 'border-black/10' => ! $dark])>
                    <x-logo-marquee :tone="$dark ? 'dark' : 'light'" />
                </div>
            </div>
        @endif
    </section>
@else
    <section id="{{ $block['id'] }}" class="px-[var(--site-gap)] pt-36 pb-20 md:pt-44">
        <div @class(['mx-auto grid max-w-[var(--content-max)] items-center gap-12', 'lg:grid-cols-2' => $image])>
            <div>
                @if (! empty($data['eyebrow']))
                    <p class="mb-5 text-sm text-neutral-600 md:text-base">{{ $data['eyebrow'] }}</p>
                @endif

                <h1 class="{{ $headingWidth }} {{ $headingType }} text-black">
                    @foreach ($headingLines as $line){{ $line }}@if (! $loop->last)<br>@endif @endforeach
                </h1>

                @if (! empty($data['subheading']))
                    <p class="mt-6 max-w-2xl text-lg text-pretty text-neutral-600">{{ $data['subheading'] }}</p>
                @endif

                @include('blocks.partials.ctas', [
                    'primary' => $data['primary_cta'] ?? null,
                    'secondary' => $data['secondary_cta'] ?? null,
                    'tone' => 'light',
                    'class' => 'mt-10',
                ])
            </div>

            @if ($image)
                <x-picture :asset="$image" :lcp="$isFirst" sizes="(min-width: 1024px) 50vw, 100vw" class="w-full rounded-3xl object-cover" />
            @endif
        </div>
    </section>
@endif
