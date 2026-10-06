{{--
    Section 7, "Cum lucrăm" (was "Pentru instituții publice"): what happens
    after launch is everyone's question, so the section is about how Webis
    works, for all clients. Each point is a general title with one concrete
    line under it; the institutional signal sits in those lines, spread out,
    and in the closing link — not in a separate panel.

    Laid out like the statement (blocks/statement): the label on the left,
    the points on the right.
--}}
@php
    $points = [
        ['title' => 'Contract, termene și livrabile clare', 'line' => 'Inclusiv pe proceduri de achiziție publică.'],
        ['title' => 'Mentenanță pe bază de contract', 'line' => 'Timpii de răspuns se stabilesc înainte de semnare, nu după prima problemă.'],
        ['title' => 'Suport în limba română', 'line' => 'De la oamenii care au construit sistemul.'],
        ['title' => 'Backup, monitorizare și actualizări', 'line' => 'Se fac fără să le cereți.'],
        ['title' => 'Integrări cu ce folosiți deja', 'line' => 'De la e-Factura și programele de contabilitate, până la sisteme interne.'],
        ['title' => 'Codul și datele rămân ale voastre', 'line' => 'Export complet, în formate deschise, oricând.'],
    ];
@endphp

<section id="cum-lucram" class="how-we-work" aria-labelledby="how-we-work-title">
    <div class="how-we-work__inner">
        <div class="how-we-work__label">
            <h2 id="how-we-work-title" class="how-we-work__eyebrow">Cum lucrăm</h2>
        </div>

        <div class="how-we-work__body">
            <ul class="how-we-work__list" role="list">
                @foreach ($points as $point)
                    <li class="how-we-work__point">
                        <h3 class="how-we-work__title">{{ $point['title'] }}</h3>
                        <p class="how-we-work__line">{{ $point['line'] }}</p>
                    </li>
                @endforeach
            </ul>

            <div class="how-we-work__foot">
                <p>Lucrăm cu instituții publice din 2016.</p>
                <x-ui.arrow-link href="/institutii-publice">Ce înseamnă pentru ele</x-ui.arrow-link>
            </div>
        </div>
    </div>
</section>
