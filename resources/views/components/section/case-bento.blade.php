{{--
    One project at a time as a composite: a violet panel with its number,
    the client and a line, a large screenshot, and two tiles under it, its
    facts (big values over small labels) and the way to it. A carousel: on
    the panel, between the arrows, a bar per project with its client's
    name, the shown one lit; it plays on its own, the lit bar filling until
    the next project, pausing while pointed at, focused or out of view, and
    stopping once someone steps through it themselves (arrows, a bar, a
    swipe). js/public/case-bento.ts; without the script, the first only
    (css/site/sections.css).

    `cases`: list of ['client', 'text', 'href', 'image' (URL or null),
    'facts' (list of [label, value])].
--}}
@props(['cases'])

@php
    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.990 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
    $count = count($cases);
@endphp

<div {{ $attributes->class('bento') }} data-bento data-reveal>
    @foreach ($cases as $case)
        <article class="bento__case" data-bento-case @if (! $loop->first) hidden @endif aria-label="{{ $case['client'] }}">
            <div class="bento__panel">
                <div class="bento__panel-top">
                    <span class="bento__no">{{ sprintf('%02d', $loop->iteration) }}</span>
                    <div>
                        <p class="bento__kicker">Client</p>
                        <h3 class="bento__client">{{ $case['client'] }}</h3>
                    </div>
                </div>

                <div class="bento__panel-foot">
                    <p class="bento__text">{{ $case['text'] }}</p>
                    @if ($count > 1)
                        @php($shown = $loop->index)
                        <div class="bento__nav">
                            <button type="button" class="bento__step bento__step--prev" aria-label="Proiectul anterior" data-bento-prev>{!! $arrow !!}</button>
                            {{-- Every project as a bar with its client's name, this one lit
                                 (and filling while the carousel plays on its own). --}}
                            <ol class="bento__dots">
                                @foreach ($cases as $other)
                                    <li>
                                        <button type="button" class="bento__dot" data-bento-go="{{ $loop->index }}" aria-label="Proiectul {{ $loop->iteration }}: {{ $other['client'] }}" @if ($loop->index === $shown) aria-current="true" @endif>
                                            <span class="bento__bar"><i></i></span>
                                            <span class="bento__dot-name">{{ $other['client'] }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ol>
                            <button type="button" class="bento__step" aria-label="Proiectul următor" data-bento-next>{!! $arrow !!}</button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bento__media">
                @if ($case['image'])
                    <img src="{{ $case['image'] }}" alt="" loading="lazy" decoding="async">
                @endif
            </div>

            <a href="{{ $case['href'] }}" class="bento__link">
                <span>Vezi proiectul</span>
                <span class="bento__link-go" aria-hidden="true">{!! $arrow !!}</span>
            </a>

            <dl class="bento__facts">
                @foreach ($case['facts'] as [$label, $value])
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </article>
    @endforeach
</div>
