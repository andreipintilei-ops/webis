{{--
    Hero block (App\Blocks\HeroBlock): the page's single <h1>.

    - centered: full-screen, text centred (or, with align "left", at the
      left of the content column), optionally with the client-logo marquee along the
      bottom. Over a dark backdrop — the animated
      gradient or an image — it is white (and the header switches to its
      light-on-dark variant); without one it is dark on the page's white.
    - split: text beside the image, on the page's white or, with a gradient
      background, on the gradient in its own section (white text).

    `$crumbs` (components/blocks): the page's breadcrumbs above the title,
    when the page has them (an industry page).
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

    // The subheading's paragraphs: a blank line between them.
    $subheading = array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', trim((string) ($data['subheading'] ?? ''))) ?: []), fn ($p) => $p !== ''));
    $headingWidth = count($headingLines) > 1 ? 'max-w-none' : 'max-w-[28ch]';

    // The home page's first, dark, full-screen hero: its backdrop is fixed
    // behind the page (blocks after it may sit on it too) and it plays the
    // intro (js/public/hero-intro.ts). The data-intro-* hooks do nothing on
    // their own.
    $centered = ($data['layout'] ?? 'split') === 'centered';

    // Anton, uppercase. Anton has one weight: it must stay font-normal, or
    // the browser draws a fake bold. The line height leaves room for
    // Romanian capitals, which carry marks on both sides of the line — Î and
    // Ă above the cap height, the commas of Ș and Ț below the baseline — so
    // over three lines the two very nearly meet.
    //
    // Sized to the title: up to 104px across a centred hero, or where the
    // editor broke the lines; a long title without breaks a step down. Beside
    // the image (split), in half the width: up to 72px for a short title,
    // 60px to 40 characters, 52px past that — about two lines each.
    $length = mb_strlen(trim((string) ($data['heading'] ?? '')));
    $headingSize = match (true) {
        $centered && (count($headingLines) > 1 || $length <= 40) => 'text-[clamp(2.75rem,7vw,6.5rem)]',
        $centered => 'text-[clamp(2.5rem,5vw,4.75rem)]',
        $length <= 24 => 'text-[clamp(2.5rem,4.6vw,4.5rem)]',
        $length <= 40 => 'text-[clamp(2.25rem,3.8vw,3.75rem)]',
        default => 'text-[clamp(2rem,3.2vw,3.25rem)]',
    };
    $headingType = "font-display {$headingSize} leading-[1.26] font-normal uppercase text-pretty";
    // The intro is the home page's alone (and its trial copies): elsewhere the
    // same hero is a plain full-screen one, its gradient its own.
    $intro = $isFirst && $dark && $centered && request()->routeIs('home', 'trials.*');
@endphp

@if ($intro)
    @push('backdrop')
        @include('blocks.partials.hero-backdrop', ['fixed' => true])
    @endpush
@endif

@if ($centered)
    <section id="{{ $block['id'] }}" @class([
        'relative isolate flex min-h-svh items-center overflow-hidden pt-32',
        // Dark and first: what follows rises over its foot (css/site/hero-parallax.css),
        // from 1024px below the fold — the whole first screen stays the hero's.
        'hero-sheet' => $isFirst && $dark,
        'lg:min-h-[calc(100svh+32px)]' => $isFirst && $dark && ! $intro,
        // Room for the logo strip along the bottom.
        'pb-44 md:pb-52' => $logos,
        'pb-32' => ! $logos,
        // Left: the header's edge padding on small screens; on wide ones the
        // content column inside sets the text in from the logo.
        'px-[var(--site-gap)] text-left' => $left,
        'justify-center px-6 text-center md:px-12' => ! $left,
        'text-white' => $dark,
        'text-black' => ! $dark,
    ]) @if ($dark) data-nav-tone="dark" @endif @if ($isFirst) data-hero-parallax @endif @if ($intro) data-intro data-backdrop-area @endif>
        {{--
            The opening hero: it scrolls with the page, no parallax, and the
            page after it slides up over it — a dark one's on a rounded top
            (css/site/hero-parallax.css).
        --}}
        @if (! $intro && $backdrop !== 'none')
            @include('blocks.partials.hero-backdrop', ['fixed' => false])
        @endif

        {{-- Left: the content column (--content-max), narrower than the header. --}}
        <div @class(['mx-auto w-full', 'max-w-[var(--content-max)]' => $left, 'max-w-5xl' => ! $left])>
            @if (! empty($crumbs))
                <div data-intro-fade>@include('blocks.partials.hero-crumbs', ['crumbs' => $crumbs, 'dark' => $dark])</div>
            @endif

            @if (! empty($data['eyebrow']))
                <p @class(['mb-5 text-sm md:mb-6 md:text-base', 'text-white/85' => $dark, 'text-neutral-600' => ! $dark]) data-intro-fade>{{ $data['eyebrow'] }}</p>
            @endif

            {{-- One masked line per typed line, so each can rise into view. --}}
            <h1 @class([$headingWidth, $headingType, 'mx-auto' => ! $left])>
                @foreach ($headingLines as $line)
                    <span class="hero-line" data-intro-line><span class="hero-line__inner">{{ $line }}</span></span>
                @endforeach
            </h1>

            @foreach ($subheading as $paragraph)
                {{-- Left: about half the content column, as the text block in the design reference. --}}
                <p @class(['text-lg text-pretty', 'mt-6' => $loop->first, 'mt-4' => ! $loop->first, 'max-w-xl' => $left, 'mx-auto max-w-2xl' => ! $left, 'text-white/85' => $dark, 'text-neutral-600' => ! $dark]) data-intro-fade>{{ $paragraph }}</p>
            @endforeach

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
                <div class="mx-auto flex max-w-[var(--content-max)] justify-start pb-4 md:justify-end md:pb-5">
                    <x-site.google-rating :tone="$dark ? 'dark' : 'light'" class="google-rating--hero" />
                </div>
                <div @class(['mx-auto max-w-[var(--content-max)] border-t pt-8 md:pt-10', 'border-white/25' => $dark, 'border-black/10' => ! $dark])>
                    <x-logo-marquee :tone="$dark ? 'dark' : 'light'" />
                </div>
            </div>
        @endif
    </section>
@else
    <section id="{{ $block['id'] }}" @class([
        'px-[var(--site-gap)] pt-36 pb-20 md:pt-44',
        'relative isolate overflow-hidden text-white md:pb-28' => $dark,
        'hero-sheet' => $isFirst && $dark,
    ]) @if ($dark) data-nav-tone="dark" @endif>
        @if ($dark)
            @include('blocks.partials.hero-backdrop', ['fixed' => false])
        @endif

        <div @class(['mx-auto grid max-w-[var(--content-max)] items-center gap-12', 'lg:grid-cols-2' => $image])>
            <div>
                @if (! empty($crumbs))
                    @include('blocks.partials.hero-crumbs', ['crumbs' => $crumbs, 'dark' => $dark])
                @endif

                @if (! empty($data['eyebrow']))
                    <p @class(['mb-5 text-sm md:text-base', 'text-white/85' => $dark, 'text-neutral-600' => ! $dark])>{{ $data['eyebrow'] }}</p>
                @endif

                <h1 @class([$headingWidth, $headingType, 'text-white' => $dark, 'text-black' => ! $dark])>
                    @foreach ($headingLines as $line){{ $line }}@if (! $loop->last)<br>@endif @endforeach
                </h1>

                @foreach ($subheading as $paragraph)
                    <p @class(['max-w-2xl text-lg text-pretty', 'mt-6' => $loop->first, 'mt-4' => ! $loop->first, 'text-white/85' => $dark, 'text-neutral-600' => ! $dark])>{{ $paragraph }}</p>
                @endforeach

                @include('blocks.partials.ctas', [
                    'primary' => $data['primary_cta'] ?? null,
                    'secondary' => $data['secondary_cta'] ?? null,
                    'tone' => $dark ? 'dark' : 'light',
                    'class' => 'mt-10',
                ])
            </div>

            @if ($image)
                <x-picture :asset="$image" :lcp="$isFirst" sizes="(min-width: 1024px) 50vw, 100vw" class="w-full rounded-3xl object-cover" />
            @endif
        </div>
    </section>
@endif
