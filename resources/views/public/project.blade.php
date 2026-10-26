{{--
    /proiect/{slug} — one client project (Public\ProjectController): the
    internal pages' hero (what it was and the year, its title, its one line,
    the live site); its images; what the client needed, what we built and
    with what; then up to three more of the same type and the footer's
    closing call. css/site/portfolio.css.

    Only what is filled in shows: a project without a gallery, or without its
    need written down yet, simply leaves that part out.
--}}
@php
    $label = collect([$project->type?->label(), $project->year])->filter()->implode(' · ');
    $client = $project->client?->name;
    $gallery = collect([$project->cover])->filter()->concat($images)->unique('id')->values();
    $details = array_filter([
        'Client' => $client,
        'De ce avea nevoie' => $project->need,
        'Ce am construit' => $project->built,
    ]);
    $stack = array_values(array_filter($project->stack ?? []));
@endphp

<x-layouts.public
    :title="$project->seo->title ?: $project->title.' · Portofoliu'"
    :description="$project->seo->description ?: $project->summary"
    :canonical="$project->seo->canonical"
    :noindex="$project->seo->noindex"
    header-theme="dark"
    footer-cta-title="Ai un proiect asemănător?"
    footer-cta-intro="Spune-ne ce vrei să construiești și revenim cu o propunere."
>
    <x-site.page-hero
        :label="$label !== '' ? $label : 'Portofoliu'"
        :title="$project->title"
        :intro="$project->summary"
        id="project-title"
    >
        @if ($project->url)
            <x-slot:after>
                <x-ui.arrow-link :href="$project->url" tone="dark" target="_blank" rel="noopener" class="project-page__live">Vezi site-ul</x-ui.arrow-link>
            </x-slot:after>
        @endif
    </x-site.page-hero>

    <section class="chapter chapter--light project-page" aria-label="Despre proiect" data-reveal-pace="fast">
        <div class="chapter__inner">
            @if ($gallery->isNotEmpty())
                <ul class="project-page__gallery" data-reveal>
                    @foreach ($gallery as $image)
                        <li data-reveal-child>
                            <x-picture :asset="$image" :lcp="$loop->first" :sizes="$loop->first ? '(min-width: 1344px) 84rem, 100vw' : '(min-width: 1024px) 50vw, 100vw'" />
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($details !== [] || $stack !== [])
                <dl class="project-page__details" data-reveal>
                    @foreach ($details as $term => $text)
                        <div data-reveal-child>
                            <dt>{{ $term }}</dt>
                            <dd>{{ $text }}</dd>
                        </div>
                    @endforeach
                    @if ($stack !== [])
                        <div data-reveal-child>
                            <dt>Tehnologii</dt>
                            <dd>
                                <ul class="project-page__stack">
                                    @foreach ($stack as $name)
                                        <li class="chapter-chip">{{ $name }}</li>
                                    @endforeach
                                </ul>
                            </dd>
                        </div>
                    @endif
                </dl>
            @endif

            <p class="project-page__back"><x-ui.arrow-link :href="route('projects.index')">Toate proiectele</x-ui.arrow-link></p>
        </div>
    </section>

    @if ($project->type)
        <x-site.related-projects :types="[$project->type]" :except="$project->id" title="Alte proiecte de acest tip" />
    @endif
</x-layouts.public>
