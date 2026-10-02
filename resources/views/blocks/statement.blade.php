{{--
    Statement block (App\Blocks\StatementBlock), after integratedbio.com:
    an asymmetric two-column section under a hairline.

    Left: a small label with a square, held in view while the right column
    scrolls past it. Right: the statement, 36–56px, its words lighting up as
    the section scrolls through (js/public/statement.ts); then a paragraph
    (18px, at most 65 characters a line) and a link.

    `$onBackdrop` (blocks.blade.php): see-through, white on the opening hero's
    fixed background, which then also shows through here. Otherwise dark on
    the page's white.

    The statement keeps its typed line breaks from desktop width up; below
    that it wraps freely. Without the script it is at full strength.
--}}
@php
    $onBackdrop ??= false;
    $lines = preg_split('/\R/', trim((string) ($data['statement'] ?? ''))) ?: [];
    $link = $data['link'] ?? null;
    $hasLink = is_array($link) && ($link['label'] ?? '') !== '' && ($link['url'] ?? '') !== '';
@endphp

@if ($lines !== [] && $lines !== [''])
    <section id="{{ $block['id'] }}" @class([
        'statement px-[var(--site-gap)]',
        // Straight after the hero: its hairline right where the hero ends, the
        // same distance below the logos as their own hairline above them.
        'statement--dark pt-0 pb-28 text-white md:pb-40' => $onBackdrop,
        'pt-24 pb-12 md:pt-36 md:pb-16' => ! $onBackdrop,
    ]) data-statement @if ($onBackdrop) data-on-backdrop data-backdrop-area data-nav-tone="dark" @endif>
        {{-- On the hero background its hairline closes the logo strip above, and
             the content sits as far below it as the section ends below it. --}}
        <div @class([
            'statement__grid mx-auto grid max-w-[var(--content-max)] gap-8 border-t md:grid-cols-12 md:gap-10',
            'pt-28 md:pt-40' => $onBackdrop,
            'pt-8 md:pt-10' => ! $onBackdrop,
        ])>
            <div class="md:col-span-4">
                @if (! empty($data['eyebrow']))
                    <p class="statement__eyebrow" data-statement-pin>
                        <span class="statement__square" aria-hidden="true"></span>
                        {{ $data['eyebrow'] }}
                    </p>
                @endif
            </div>

            {{-- Two thirds, ending where the content column ends. --}}
            <div class="md:col-span-8">
                {{-- Each word its own span, so it can light up; read as one sentence. --}}
                <h2 class="statement__text" data-statement-text>
                    @foreach ($lines as $line)
                        @foreach (preg_split('/\s+/', trim($line)) ?: [] as $word)
                            <span class="statement__word">{{ $word }}</span>
                        @endforeach
                        @if (! $loop->last)
                            <br class="hidden lg:inline">
                        @endif
                    @endforeach
                </h2>

                @if (! empty($data['text']))
                    <p class="statement__body mt-8 max-w-[65ch] text-lg text-pretty md:mt-10">{{ $data['text'] }}</p>
                @endif

                @if ($hasLink)
                    <div class="mt-10 md:mt-12">
                        <x-ui.arrow-link :href="$link['url']" :tone="$onBackdrop ? 'dark' : 'light'">{{ $link['label'] }}</x-ui.arrow-link>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
