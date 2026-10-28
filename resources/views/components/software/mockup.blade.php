{{--
    A module's mock-up on Software la comandă (02, the explorer): one white
    application window on the purple UI card of the home page's service
    cards — the lavender ground, its faint grid and purple glow, the violet
    to purple tile — in HTML, sized in em off the card's width so it reads
    the same at any size (css/site/software.css, .sw-mock). Illustrative
    data, Romanian labels, no real clients. Decorative: the panel's own text
    says what it shows.

    `variant`: erp, crm, registratura, aprobari, rapoarte, integrari.
--}}
@props(['variant'])

@php
    $window = [
        'erp' => ['layers', 'Stocuri', 'Depozit central'],
        'crm' => ['users', 'Oferte', 'Octombrie 2026'],
        'registratura' => ['inbox', 'Registru general', '2026'],
        'aprobari' => ['workflow', 'Aprobări', 'Referat nr. 2381'],
        'rapoarte' => ['chart', 'Raport lunar', 'Octombrie 2026'],
        'integrari' => ['plug', 'Integrări', '3 conexiuni active'],
    ][$variant];
@endphp

<div class="sw-mock sw-mock--{{ $variant }}" aria-hidden="true">
    <div class="sw-mock__window">
        <div class="sw-mock__bar">
            <span class="sw-mock__tile"><x-software.icon :name="$window[0]" /></span>
            <b>{{ $window[1] }}</b>
            <span class="sw-mock__crumb">{{ $window[2] }}</span>
            <span class="sw-mock__dots"><i></i><i></i><i></i></span>
        </div>

        <div class="sw-mock__body">
            @switch($variant)
                @case('erp')
                    <div class="sw-mock__tools">
                        <span class="sw-mock__search"><x-software.icon name="search" />Caută produs</span>
                        <span class="sw-mock__button"><x-software.icon name="plus" />Recepție</span>
                    </div>
                    <table class="sw-stock">
                        <thead>
                            <tr><th>Produs</th><th>Cantitate</th><th>Locație</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Hârtie copiator A4</td><td>240 top</td><td>Depozit central</td></tr>
                            <tr class="is-low"><td>Toner imprimantă <span class="sw-pill-warn"><x-software.icon name="alert" />Stoc minim</span></td><td>3 buc.</td><td>Depozit central</td></tr>
                            <tr><td>Dosare cu șină</td><td>1.150 buc.</td><td>Magazie etaj 2</td></tr>
                            <tr><td>Bibliorafturi</td><td>64 buc.</td><td>Magazie etaj 2</td></tr>
                            <tr><td>Mănuși nitril</td><td>86 cutii</td><td>Laborator</td></tr>
                        </tbody>
                    </table>
                    @break

                @case('crm')
                    @php
                        $columns = [
                            ['Ofertă trimisă', [['Clinică stomatologică', '12.400 lei', 'AP'], ['Service auto', '8.900 lei', 'MR'], ['Școală gimnazială', '5.300 lei', 'ID']]],
                            ['Negociere', [['Producător mobilier', '27.500 lei', 'RT'], ['Cabinet de avocatură', '9.800 lei', 'AP']]],
                            ['Câștigat', [['Firmă de transport', '18.200 lei', 'EM'], ['Primărie de comună', '31.000 lei', 'MR']]],
                        ];
                    @endphp
                    <div class="sw-pipeline">
                        @foreach ($columns as [$stage, $deals])
                            <div @class(['sw-pipeline__col', 'is-won' => $loop->last])>
                                <p class="sw-pipeline__head">{{ $stage }} <span>{{ count($deals) }}</span></p>
                                @foreach ($deals as [$who, $value, $owner])
                                    <div class="sw-deal">
                                        <b>{{ $who }}</b>
                                        <span class="sw-deal__foot"><span>{{ $value }}</span><i>{{ $owner }}</i></span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                    @break

                @case('registratura')
                    @php
                        $entries = [
                            ['2381', '14.10.2026', 'Cerere concediu', 'Intrare'],
                            ['2380', '14.10.2026', 'Răspuns la petiție', 'Ieșire'],
                            ['2379', '13.10.2026', 'Factură furnizor', 'Intrare'],
                            ['2378', '13.10.2026', 'Referat de necesitate', 'Intern'],
                            ['2377', '12.10.2026', 'Contract de prestări servicii', 'Ieșire'],
                        ];
                    @endphp
                    <div class="sw-mock__tools">
                        <span class="sw-mock__search"><x-software.icon name="search" />Caută în registru</span>
                        <span class="sw-mock__button"><x-software.icon name="plus" />Înregistrare</span>
                    </div>
                    <p class="sw-register__next">Următorul număr se alocă automat: <b>Nr. 2382</b></p>
                    <ul class="sw-register">
                        @foreach ($entries as [$number, $date, $title, $way])
                            <li>
                                <span class="sw-register__no">Nr. {{ $number }} / {{ $date }}</span>
                                <span class="sw-register__title">{{ $title }}</span>
                                <span class="sw-way sw-way--{{ \Illuminate\Support\Str::slug($way) }}">{{ $way }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @break

                @case('aprobari')
                    <div class="sw-request">
                        <div class="sw-request__head">
                            <span class="sw-request__icon"><x-software.icon name="sheet" /></span>
                            <span><b>Referat de necesitate · laptopuri</b><small>Trimis de Elena M. · 4.800 lei</small></span>
                            <span class="sw-status sw-status--work">În aprobare</span>
                        </div>
                        <ol class="sw-flow">
                            <li class="is-done"><span class="sw-flow__dot"><x-software.icon name="check" /></span><b>Referent</b><small>Aprobat · 14.10, 09:12</small></li>
                            <li class="is-done"><span class="sw-flow__dot"><x-software.icon name="check" /></span><b>Șef serviciu</b><small>Aprobat · 14.10, 11:40</small></li>
                            <li class="is-now"><span class="sw-flow__dot"></span><b>Director</b><small>Așteaptă aprobarea</small></li>
                        </ol>
                    </div>
                    <p class="sw-toast"><x-software.icon name="mail" />Notificare trimisă: <b>Director</b></p>
                    @break

                @case('rapoarte')
                    <div class="sw-report">
                        <div class="sw-report__figures">
                            <p class="sw-figure"><small>Cereri procesate</small><b>1.284</b><span>+12% față de septembrie</span></p>
                            <p class="sw-figure"><small>Timp mediu de aprobare</small><b>2,4 zile</b><span>−0,6 zile</span></p>
                        </div>
                        <div class="sw-chart">
                            <p class="sw-chart__head"><b>Cereri pe lună</b><span class="sw-mock__button"><x-software.icon name="download" />Exportă PDF</span></p>
                            <div class="sw-chart__bars">
                                @foreach (['Mai' => 52, 'Iun' => 64, 'Iul' => 48, 'Aug' => 58, 'Sep' => 76, 'Oct' => 92] as $month => $height)
                                    <span @class(['is-now' => $loop->last]) style="--h: {{ $height }}%"><i></i><small>{{ $month }}</small></span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @break

                @case('integrari')
                    <div class="sw-links">
                        <svg class="sw-links__lines" viewBox="0 0 400 220" preserveAspectRatio="none" fill="none">
                            <path d="M200 110 C 140 110 120 40 70 40" />
                            <path d="M200 110 C 260 110 280 40 330 40" />
                            <path d="M200 110 V 190" />
                        </svg>
                        <span class="sw-node sw-node--app"><span class="sw-mock__tile"><x-software.icon name="database" /></span><b>Aplicația ta</b></span>
                        <span class="sw-node sw-node--a"><x-software.icon name="receipt" /><b>e-Factura</b><small>Facturi transmise</small></span>
                        <span class="sw-node sw-node--b"><x-software.icon name="landmark" /><b>SPV</b><small>Mesaje preluate</small></span>
                        <span class="sw-node sw-node--c"><x-software.icon name="plug" /><b>Programul tău</b><small>Date sincronizate</small></span>
                    </div>
                    @break
            @endswitch
        </div>
    </div>
</div>
