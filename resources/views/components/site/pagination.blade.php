{{--
    A listing's pages (/blog, and any list like it): the previous page, the
    numbers — the first, the last and those around the current one, with a
    gap between — and the next page. The current page is the violet one.
    Plain loads, never through the page transition (data-no-transition).
    css/site/pagination.css.

    `paginator`: an Illuminate LengthAwarePaginator. Shows nothing for a
    single page.
--}}
@props(['paginator', 'label' => 'Paginare'])

@php
    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    // 1 … 4 5 6 … 12: the ends, and one either side of the current page.
    $pages = collect(range(1, $last))
        ->filter(fn (int $page) => $page === 1 || $page === $last || abs($page - $current) <= 1)
        ->values();
@endphp

@if ($paginator->hasPages())
    <nav {{ $attributes->class('pagination') }} aria-label="{{ $label }}">
        @if ($paginator->onFirstPage())
            <span class="pagination__step pagination__step--prev" aria-hidden="true">{!! $arrow !!}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pagination__step pagination__step--prev" rel="prev" aria-label="Pagina anterioară" data-no-transition>{!! $arrow !!}</a>
        @endif

        <ol class="pagination__pages">
            @foreach ($pages as $page)
                @if ($loop->index > 0 && $page - $pages[$loop->index - 1] > 1)
                    <li class="pagination__gap" aria-hidden="true">…</li>
                @endif
                <li>
                    @if ($page === $current)
                        <span class="pagination__page" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="pagination__page" aria-label="Pagina {{ $page }}" data-no-transition>{{ $page }}</a>
                    @endif
                </li>
            @endforeach
        </ol>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pagination__step" rel="next" aria-label="Pagina următoare" data-no-transition>{!! $arrow !!}</a>
        @else
            <span class="pagination__step" aria-hidden="true">{!! $arrow !!}</span>
        @endif
    </nav>
@endif
