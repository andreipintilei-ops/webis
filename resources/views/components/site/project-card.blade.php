{{--
    One client project as a card (.work-card, css/site/portfolio.css), as a
    list item: its cover (else a plain ground with the client's name), what
    it was and the year, its title, the client, and its one line. Links to
    /proiect/{slug}. Used by /portofoliu and the related projects.

    `project`: an App\Models\Project, with its client and cover loaded.
    `index`: its place in the list (the reveal's stagger).
--}}
@props(['project', 'index' => 0])

@php
    $cover = $project->cover;
    $client = $project->client?->name;
@endphp

<li style="--i: {{ $index }}" data-reveal-child>
    <a href="{{ route('projects.show', $project->slug) }}" class="work-card">
        <span class="work-card__media">
            @if ($cover?->file())
                <x-picture :asset="$cover" :alt="''" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" />
            @else
                {{-- PLACEHOLDER: until the project has a cover. --}}
                <span class="work-card__blank" aria-hidden="true">{{ $client ?? $project->title }}</span>
            @endif
        </span>
        <span class="work-card__meta">
            @if ($project->type)
                <span class="work-card__type">{{ $project->type->label() }}</span>
            @endif
            @if ($project->year)
                <span>{{ $project->year }}</span>
            @endif
        </span>
        <span class="work-card__title">{{ $project->title }}</span>
        @if ($client && $client !== $project->title)
            <span class="work-card__client">{{ $client }}</span>
        @endif
        @if ($project->summary)
            <span class="work-card__summary">{{ $project->summary }}</span>
        @endif
    </a>
</li>
