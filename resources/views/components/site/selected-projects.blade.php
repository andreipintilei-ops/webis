{{--
    Section 4 prototype. Replace these example stories with approved project content.

    `layout`: "grid" — the three stories side by side; "showcase" (the home
    page) — two columns of large cards: on the left one held in view, with
    the title and the details of the project in view, and on the right the
    three projects' interfaces scrolling past it (css/site/project-showcase.css,
    js/public/project-showcase.ts). On phones the cards simply stack, each
    project with its own details.
--}}
@props(['layout' => 'grid'])

@php
    $examples = [
        [
            'audience' => 'Universitate',
            'organization' => '[Numele universității]',
            'variant' => 'university',
            'need' => 'Cereri și documente gestionate în sisteme separate.',
            'built' => 'O platformă pentru centralizarea cererilor și aprobărilor.',
            'result' => '[Utilizatori activi · anul lansării]',
        ],
        [
            'audience' => 'Instituție publică',
            'organization' => '[Numele instituției]',
            'variant' => 'institution',
            'need' => 'Un proces de lucru cu multe etape manuale.',
            'built' => 'Un sistem de evidență, urmărire și raportare.',
            'result' => '[Operațiuni procesate lunar]',
        ],
        [
            'audience' => 'Companie',
            'organization' => '[Numele companiei]',
            'variant' => 'company',
            'need' => 'Date și operațiuni împărțite între mai multe instrumente.',
            'built' => 'O aplicație internă care conectează echipele și procesele.',
            'result' => '[Timp economisit · volum de lucru]',
        ],
    ];

    $facts = fn (array $example): array => [
        'Ce aveau nevoie' => $example['need'],
        'Ce am construit' => $example['built'],
        'Ce face acum' => $example['result'],
    ];
@endphp

@if ($layout === 'showcase')
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
