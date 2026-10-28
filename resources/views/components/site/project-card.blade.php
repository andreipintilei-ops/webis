{{--
    One client project as the site's notched card (.post-card, as the
    articles', with /web's screenshot on top: .web-project, css/site/web.css),
    as a list item: its cover (else a plain ground), what it was and the
    year, its title, and its one line. Links to /proiect/{slug}. Used by the
    related projects at the foot of a project's page.

    `project`: an App\Models\Project, with its cover loaded.
    `index`: its place in the list (the reveal's stagger).
--}}
@props(['project', 'index' => 0])

@php
    $cover = $project->cover;
    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<li style="--i: {{ $index }}" data-reveal-child>
    <a href="{{ route('projects.show', $project->slug) }}" class="post-card web-project">
        <span class="post-card__frame" aria-hidden="true">
            <span class="post-card__bg"></span>
            <span class="post-card__button"><span class="post-card__arrow">{!! $arrow !!}</span><span class="post-card__arrow post-card__arrow--next">{!! $arrow !!}</span></span>
        </span>
        <span class="web-project__media">
            @if ($cover?->file())
                <x-picture :asset="$cover" :alt="''" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" />
            @endif
        </span>
        <span class="post-card__meta">
            @if ($project->type)
                <span class="post-card__category">{{ $project->type->label() }}</span>
            @endif
            @if ($project->year)
                <span>{{ $project->year }}</span>
            @endif
        </span>
        <span class="post-card__title"><span>{{ $project->title }}</span></span>
        @if ($project->summary)
            <span class="post-card__foot">{{ $project->summary }}</span>
        @endif
    </a>
</li>
