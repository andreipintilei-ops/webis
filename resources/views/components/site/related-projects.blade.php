{{--
    Up to three published client projects of the given types, as /web's
    project cards (components/site/project-card), with the way to the rest
    of them on /portofoliu. At the foot of a project's page. Client work
    only — never a product. Nothing at all when there is no such project.

    `types`: list of App\Enums\ProjectType. `except`: a project's id to leave
    out (the one being shown). `title`: the section's title.
--}}
@props(['types' => [], 'except' => null, 'title' => 'Proiecte pentru clienți'])

@php
    use App\Models\Project;

    $projects = $types === [] ? collect() : Project::query()
        ->published()
        ->whereIn('type', $types)
        ->when($except, fn ($query, $id) => $query->whereKeyNot($id))
        ->with('cover.media')
        ->orderBy('sort_order')
        ->orderByDesc('year')
        ->latest('id')
        ->limit(3)
        ->get();

    // One type: straight to its filter.
    $more = count($types) === 1 ? route('projects.index', ['tip' => $types[0]->value]) : route('projects.index');
@endphp

@if ($projects->isNotEmpty())
    <section {{ $attributes->class('chapter chapter--light related-projects') }} aria-labelledby="related-projects-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <p class="chapter__label">Portofoliu</p>
                    <h2 id="related-projects-title" class="chapter__title">{{ $title }}</h2>
                </div>
                <x-ui.arrow-link :href="$more">Vezi toate proiectele</x-ui.arrow-link>
            </header>

            <ul class="web-projects__list" data-reveal>
                @foreach ($projects as $project)
                    <x-site.project-card :project="$project" :index="$loop->index" />
                @endforeach
            </ul>
        </div>
    </section>
@endif
