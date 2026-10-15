{{--
    Every project as a list, after dennissnellenberg.com/work: rows between
    hairlines — the client large, what it was and its site in quiet columns —
    each a link to the project. With a mouse, a preview of the row pointed at
    follows the pointer, sliding from one project's screenshot to the next,
    with a round "Vezi" beside it (js/public/work-list.ts); the other rows
    dim. On phones each row shows its screenshot small, beside the name.
    css/site/work-list.css.

    Filtered by type in place: every row is here, those of other types
    hidden; the page's filter links (data-work-filter) show and hide them.

    `rows`: list of ['href', 'client', 'type' (App\Enums\ProjectType),
    'site' (or null), 'image' (a URL, or null)]. `filter`: the type shown
    at first (App\Enums\ProjectType), or null for all.
--}}
@props(['rows', 'filter' => null])

@php
    $shown = fn (array $row): bool => $filter === null || $row['type'] === $filter;
@endphp

<div class="work-list" data-work-list>
    <div class="work-list__columns" aria-hidden="true">
        <span>Client</span><span>Tip</span><span>Site</span>
    </div>

    <ul class="work-list__rows" data-reveal>
        @foreach ($rows as $row)
            <li data-reveal-child data-work-type="{{ $row['type']?->value }}" @unless ($shown($row)) hidden @endunless>
                <a href="{{ $row['href'] }}" class="work-list__row" data-work-row="{{ $loop->index }}">
                    <span class="work-list__thumb" aria-hidden="true">
                        @if ($row['image'])
                            <img src="{{ $row['image'] }}" alt="" loading="lazy" decoding="async">
                        @endif
                    </span>
                    <span class="work-list__client">{{ $row['client'] }}</span>
                    <span class="work-list__type">{{ $row['type']?->label() }}</span>
                    <span class="work-list__site">{{ $row['site'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <p class="portfolio-empty" data-work-empty @if (collect($rows)->contains($shown)) hidden @endif>Încă nu am publicat proiecte de acest tip.</p>

    {{-- The preview and its round label: moved to <body> by the script, so
         they follow the pointer above the smooth-scrolled page. --}}
    <div class="work-list__preview" aria-hidden="true" data-work-preview style="--n: {{ count($rows) }}">
        <div class="work-list__track" data-work-track>
            @foreach ($rows as $row)
                <span class="work-list__slide">
                    @if ($row['image'])
                        <img src="{{ $row['image'] }}" alt="" loading="lazy" decoding="async">
                    @endif
                </span>
            @endforeach
        </div>
    </div>
    <span class="work-list__cursor" aria-hidden="true" data-work-cursor>Vezi</span>
</div>
