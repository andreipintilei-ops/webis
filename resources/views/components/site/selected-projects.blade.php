{{--
    Section 4 prototype. DEMO DATA: the three stories below are invented
    (no real client, figure or date) — replace them with approved project
    content before launch.

    `layout`: "grid" — the three stories side by side; "showcase" (the home
    page) — two columns of large cards: on the left one held in view, with
    the title and the details of the project in view, and on the right the
    three projects' interfaces scrolling past it (css/site/project-showcase.css,
    js/public/project-showcase.ts). On phones the cards simply stack, each
    project with its own details. "list" (the violet pages) — the projects as
    rows, each with a thumbnail of its interface (css/site/project-list.css).
    "accordion" — rows that open, one at a time, into a short case
    (css/site/project-accordion.css). "steps" — numbered steps beside very
    tall patterned cards, each step held while its card passes
    (css/site/project-steps.css, js/public/project-steps.ts).
--}}
@props(['layout' => 'grid'])

@php
    $examples = [
        [
            'audience' => 'Universitate',
            'organization' => 'Gestiunea proiectelor de cercetare',
            'variant' => 'university',
            'need' => 'Proiecte și bugete urmărite în Excel.',
            'built' => 'O platformă pentru proiecte și deconturi.',
            'result' => '240 de proiecte gestionate.',
        ],
        [
            'audience' => 'Instituție publică',
            'organization' => 'Registru electronic de documente',
            'variant' => 'institution',
            'need' => 'Documente urmărite în registre separate.',
            'built' => 'Un sistem de înregistrare și urmărire.',
            'result' => '3.500 de documente pe lună.',
        ],
        [
            'audience' => 'Companie',
            'organization' => 'Gestiunea comenzilor',
            'variant' => 'company',
            'need' => 'Comenzi, stoc și facturi în trei programe.',
            'built' => 'O aplicație internă care le leagă.',
            'result' => 'Comenzi procesate de două ori mai repede.',
        ],
    ];

    $facts = fn (array $example): array => [
        'Ce aveau nevoie' => $example['need'],
        'Ce am construit' => $example['built'],
        'Ce face acum' => $example['result'],
    ];
@endphp

@if ($layout === 'list')
{{-- After the list of recent work on dennissnellenberg.com: the projects as
     rows between hairlines, each with a thumbnail of its interface on its own
     colour; hovering one brings it forward and dims the rest
     (css/site/project-list.css). --}}
<section id="proiecte-lista" class="project-list" aria-labelledby="projects-list-title">
    <div class="project-list__inner">
        <header class="project-list__head">
            <div class="md:col-span-7">
                <p class="project-list__label">Proiecte selectate</p>
                <h2 id="projects-list-title" class="project-list__title">Ce am construit,<br>pe scurt.</h2>
            </div>
            <p class="project-list__intro">Universități, instituții publice și companii. Nevoi diferite, soluții construite în jurul lor.</p>
        </header>

        <div class="project-list__columns" aria-hidden="true">
            <span>Proiect</span><span>Rezultat</span>
        </div>

        <ol class="project-list__rows">
            @foreach ($examples as $example)
                <li class="project-list__row" data-project-list-row="{{ $loop->index }}">
                    <span class="project-list__number">{{ sprintf('%02d', $loop->iteration) }}</span>

                    <div class="project-list__thumb" aria-hidden="true">
                        <x-site.project-interface :variant="$example['variant']" />
                    </div>

                    <div class="project-list__main">
                        <span class="project-list__audience">{{ $example['audience'] }}</span>
                        <h3 class="project-list__name">{{ $example['organization'] }}</h3>
                        {{-- From what they needed to what was built. --}}
                        <p class="project-list__story">
                            <span class="sr-only">Ce aveau nevoie: </span>{{ $example['need'] }}
                            <span class="project-list__arrow" aria-hidden="true">→</span>
                            <span class="sr-only">Ce am construit: </span>{{ $example['built'] }}
                        </p>
                    </div>

                    <p class="project-list__result"><span class="sr-only">Ce face acum: </span>{{ $example['result'] }}</p>
                </li>
            @endforeach
        </ol>

        <div class="project-list__foot">
            <x-ui.pill :href="route('projects.index')">Toți clienții</x-ui.pill>
        </div>
    </div>
</section>
@elseif ($layout === 'accordion')
{{-- The projects as rows that open, one at a time, into a short case: the
     interface on the project's colour, the three facts and a link. Native
     <details> (keyboard-ready, working without the script); the script
     animates the opening and closing and keeps one open at a time
     (css/site/project-accordion.css, js/public/project-accordion.ts). --}}
<section id="proiecte-detalii" class="project-accordion" aria-labelledby="projects-accordion-title" data-project-accordion>
    <div class="project-accordion__inner">
        <header class="project-accordion__head">
            <div class="md:col-span-7">
                <p class="project-accordion__label">Proiecte selectate</p>
                <h2 id="projects-accordion-title" class="project-accordion__title">Ce am construit,<br>pe scurt.</h2>
            </div>
            <p class="project-accordion__intro">Universități, instituții publice și companii. Nevoi diferite, soluții construite în jurul lor.</p>
        </header>

        <div class="project-accordion__list">
            @foreach ($examples as $example)
                <details @class(['project-accordion__item', 'is-open' => $loop->first]) @if ($loop->first) open @endif data-accordion-item>
                    <summary class="project-accordion__summary">
                        <span class="project-accordion__number">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <h3 class="project-accordion__name">{{ $example['organization'] }}</h3>
                        <span class="project-accordion__audience">{{ $example['audience'] }}</span>
                        <span class="project-accordion__icon" aria-hidden="true"></span>
                    </summary>

                    <div class="project-accordion__case" data-accordion-case>
                        <div class="project-accordion__visual" aria-hidden="true">
                            <x-site.project-interface :variant="$example['variant']" />
                        </div>
                        <div class="project-accordion__story">
                            <dl class="project-accordion__facts">
                                @foreach ($facts($example) as $term => $detail)
                                    <div><dt>{{ $term }}</dt><dd>{{ $detail }}</dd></div>
                                @endforeach
                            </dl>
                            {{-- TODO: the project's own page. --}}
                            <x-ui.arrow-link :href="route('projects.index')" class="project-accordion__link">Vezi proiectul</x-ui.arrow-link>
                        </div>
                    </div>
                </details>
            @endforeach
        </div>

        <div class="project-accordion__foot">
            <x-ui.pill :href="route('projects.index')">Toți clienții</x-ui.pill>
        </div>
    </div>
</section>
@elseif ($layout === 'steps')
{{-- The projects as numbered steps, after the walkthrough on sendpotion.com:
     each a row — on the left its step (a large number, the audience, the
     name, one line, the result), held in view while its card passes and then
     pushed on by the next; on the right a very tall purple card with its own
     pattern and the interface in a large window running off its edges,
     drifting as the page scrolls. js/public/project-steps.ts,
     css/site/project-steps.css. --}}
@php
    // Per project: the word of its name to highlight.
    $stepMarks = [
        'university' => 'proiectelor',
        'institution' => 'documente',
        'company' => 'comenzilor',
    ];
@endphp
<section id="proiecte-pasi" class="project-steps" aria-labelledby="projects-steps-title" data-project-steps data-nav-tone="dark">
    <div class="project-steps__inner">
        <header class="project-steps__head">
            <div class="md:col-span-7">
                <p class="project-steps__label">Proiecte selectate</p>
                <h2 id="projects-steps-title" class="project-steps__title">Ce am construit,<br>pe scurt.</h2>
            </div>
            <p class="project-steps__intro">Universități, instituții publice și companii. Nevoi diferite, soluții construite în jurul lor.</p>
        </header>

        <ol class="project-steps__rows">
            @foreach ($examples as $example)
                <li class="project-steps__row" data-step-row>
                    <div class="project-steps__text" data-step-text>
                        {{-- A small index and the audience; the name leads. --}}
                        <p class="project-steps__meta" data-step-part><span>{{ sprintf('%02d', $loop->iteration) }}</span>{{ $example['audience'] }}</p>
                        @php($mark = $stepMarks[$example['variant']] ?? null)
                        {{-- One word of the name on a highlight, as on the reference. --}}
                        <h3 class="project-steps__name" data-step-part>{!! $mark ? str_replace(e($mark), '<mark>'.e($mark).'</mark>', e($example['organization'])) : e($example['organization']) !!}</h3>
                        <p class="project-steps__line" data-step-part>{{ $example['built'] }}</p>
                        <div class="project-steps__result" data-step-part>
                            <span class="project-steps__result-label">Ce face acum</span>
                            <p>{{ $example['result'] }}</p>
                        </div>
                    </div>

                    <div class="project-steps__visual" aria-hidden="true">
                        <div class="project-steps__window" data-step-window>
                            <x-site.project-interface :variant="$example['variant']" />
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>

        <div class="project-steps__foot">
            <x-ui.pill :href="route('projects.index')">Toți clienții</x-ui.pill>
        </div>
    </div>
</section>
@elseif ($layout === 'showcase')
<section id="proiecte-selectate" class="project-showcase" aria-labelledby="selected-projects-title" data-project-showcase>
    <div class="project-showcase__inner">
        <div class="project-showcase__aside">
            {{-- The card held in view (pinned by the script from desktop width). --}}
            <div class="project-showcase__lead" data-showcase-lead>
                <div>
                    <p class="selected-projects__eyebrow">Proiecte selectate</p>
                    <h2 id="selected-projects-title" class="project-showcase__title">Ce am construit,<br>pe scurt.</h2>
                    <p class="project-showcase__intro">Universități, instituții publice și companii. Nevoi diferite, soluții construite în jurul lor.</p>
                </div>

                {{-- The details of the project in view, one at a time (desktop). --}}
                <div class="project-showcase__panels">
                    @foreach ($examples as $example)
                        <div @class(['project-showcase__panel', 'is-active' => $loop->first]) data-showcase-panel>
                            <p class="project-showcase__count">
                                <span>{{ sprintf('%02d', $loop->iteration) }} / {{ sprintf('%02d', count($examples)) }}</span>
                                {{ $example['audience'] }}
                            </p>
                            <h3 class="project-showcase__name">{{ $example['organization'] }}</h3>
                            <dl class="project-showcase__facts">
                                @foreach ($facts($example) as $term => $detail)
                                    <div><dt>{{ $term }}</dt><dd>{{ $detail }}</dd></div>
                                @endforeach
                            </dl>
                        </div>
                    @endforeach
                </div>

                <x-ui.arrow-link :href="route('projects.index')" class="project-showcase__link">Toți clienții</x-ui.arrow-link>
            </div>
        </div>

        <div class="project-showcase__cards" data-showcase-cards>
            @foreach ($examples as $example)
                <article class="project-showcase__card project-showcase__card--{{ $example['variant'] }}" data-showcase-card>
                    <div class="project-showcase__visual">
                        <span class="project-showcase__audience">{{ sprintf('%02d', $loop->iteration) }} · {{ $example['audience'] }}</span>
                        <div class="project-showcase__screen" data-showcase-screen>
                            <x-site.project-interface :variant="$example['variant']" />
                        </div>
                    </div>

                    {{-- The same details, under each project on phones and tablets. --}}
                    <div class="project-showcase__details">
                        <h3 class="project-showcase__name">{{ $example['organization'] }}</h3>
                        <dl class="project-showcase__facts">
                            @foreach ($facts($example) as $term => $detail)
                                <div><dt>{{ $term }}</dt><dd>{{ $detail }}</dd></div>
                            @endforeach
                        </dl>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@else
<section id="proiecte-selectate" class="selected-projects" aria-labelledby="selected-projects-title">
    <div class="selected-projects__inner">
        <div class="selected-projects__header">
            <div>
                <p class="selected-projects__eyebrow">Proiecte selectate</p>
                <h2 id="selected-projects-title" class="selected-projects__title">Ce am construit,<br>pe scurt.</h2>
            </div>
            <div class="selected-projects__intro">
                <p>Universități, instituții publice și companii. Nevoi diferite, soluții construite în jurul lor.</p>
            </div>
        </div>

        <div class="selected-projects__grid">
            @foreach ($examples as $example)
                <article class="project-story" aria-labelledby="project-story-{{ $loop->iteration }}">
                    <div class="project-story__visual project-story__visual--{{ $example['variant'] }}">
                        <span class="project-story__audience">{{ $example['audience'] }}</span>
                        <x-site.project-interface :variant="$example['variant']" />
                    </div>

                    <div class="project-story__heading">
                        <span class="project-story__number" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <h3 id="project-story-{{ $loop->iteration }}" class="project-story__name">{{ $example['organization'] }}</h3>
                    </div>

                    <dl class="project-story__facts">
                        <div class="project-story__fact">
                            <dt>Ce aveau nevoie</dt>
                            <dd>{{ $example['need'] }}</dd>
                        </div>
                        <div class="project-story__fact">
                            <dt>Ce am construit</dt>
                            <dd>{{ $example['built'] }}</dd>
                        </div>
                        <div class="project-story__fact project-story__fact--result">
                            <dt>Ce face acum</dt>
                            <dd>{{ $example['result'] }}</dd>
                        </div>
                    </dl>
                </article>
            @endforeach
        </div>

        <div class="selected-projects__foot">
            <x-ui.arrow-link :href="route('projects.index')">Toți clienții</x-ui.arrow-link>
        </div>
    </div>
</section>
@endif
