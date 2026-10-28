{{--
    A product's screen on Produse SaaS, drawn in HTML in the software page's
    mock-up card (components/software/mockup: the lavender ground, the white
    window running off its foot, everything sized in em off the card's
    width; css/site/software.css, .sw-mock). The inside of each screen is
    css/site/products.css. Illustrative data, Romanian labels, no real
    patients, projects or people. Decorative: the product's own text says
    what it does.

    `variant`: smilesoft, rims, bugetare, confirmo.
--}}
@props(['variant'])

@php
    $window = [
        'smilesoft' => ['calendar', 'Programări', 'Azi, marți 14 octombrie'],
        'rims' => ['flask', 'Proiecte de cercetare', '2026'],
        'bugetare' => ['vote', 'Propuneri', 'Ediția 2026 · vot deschis'],
        'confirmo' => ['calendar', 'Programare online', 'Salon Lila'],
    ][$variant];
@endphp

<div class="sw-mock saas-mock saas-mock--{{ $variant }}" aria-hidden="true">
    <div class="sw-mock__window">
        <div class="sw-mock__bar">
            <span class="sw-mock__tile"><x-software.icon :name="$window[0]" /></span>
            <b>{{ $window[1] }}</b>
            <span class="sw-mock__crumb">{{ $window[2] }}</span>
            <span class="sw-mock__dots"><i></i><i></i><i></i></span>
        </div>

        <div class="sw-mock__body">
            @switch($variant)
                @case('smilesoft')
                    {{-- A clinic's day: a column per doctor, the appointments in it. --}}
                    <div class="saas-day">
                        <span class="saas-day__corner"></span>
                        <b>Dr. Ionescu</b>
                        <b>Dr. Popa</b>
                        @foreach (['09:00', '10:00', '11:00', '12:00'] as $hour)
                            <small style="grid-row: {{ $loop->iteration + 1 }}">{{ $hour }}</small>
                        @endforeach
                        <span class="saas-visit saas-visit--a" style="grid-column: 2; grid-row: 2 / 4"><b>Andrei M.</b>Tratament canal</span>
                        <span class="saas-visit saas-visit--b" style="grid-column: 3; grid-row: 2"><b>Ioana P.</b>Control</span>
                        <span class="saas-visit saas-visit--c" style="grid-column: 3; grid-row: 3 / 5"><b>Mihai R.</b>Implant · etapa 2</span>
                        <span class="saas-visit saas-visit--b" style="grid-column: 2; grid-row: 5"><b>Elena D.</b>Detartraj</span>
                    </div>
                    @break

                @case('rims')
                    {{-- Research projects: funder, budget, how far along. --}}
                    <div class="sw-mock__tools">
                        <span class="sw-mock__search"><x-software.icon name="search" />Caută proiect</span>
                        <span class="sw-mock__button"><x-software.icon name="plus" />Proiect nou</span>
                    </div>
                    <table class="saas-projects">
                        <thead><tr><th>Proiect</th><th>Finanțator</th><th>Buget</th><th>Progres</th></tr></thead>
                        <tbody>
                            @foreach ([['Materiale compozite', 'UEFISCDI', '1,2 mil. lei', 72], ['Senzori pentru agricultură', 'Horizon Europe', '640.000 €', 45], ['Digitalizarea arhivelor', 'PNRR', '890.000 lei', 88], ['Energie din biomasă', 'UEFISCDI', '420.000 lei', 23]] as [$name, $funder, $budget, $done])
                                <tr>
                                    <td><b>{{ $name }}</b></td>
                                    <td>{{ $funder }}</td>
                                    <td>{{ $budget }}</td>
                                    <td><span class="saas-progress" style="--p: {{ $done }}%"><i></i></span>{{ $done }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @break

                @case('bugetare')
                    {{-- Citizens' proposals and their votes. --}}
                    <ol class="saas-ideas">
                        @foreach ([['Parc pentru copii pe strada Florilor', 'Cartierul Nord', 1284, 100], ['Piste de biciclete spre centru', 'Centru', 976, 76], ['Iluminat pe aleile din parc', 'Cartierul Sud', 712, 55], ['Bănci și fântâni în piață', 'Centru', 438, 34]] as [$idea, $area, $votes, $share])
                            <li>
                                <p><b>{{ $idea }}</b><small>{{ $area }}</small></p>
                                <span class="saas-votes" style="--v: {{ $share }}%"><i></i></span>
                                <span class="saas-ideas__count">{{ number_format($votes, 0, ',', '.') }} voturi</span>
                            </li>
                        @endforeach
                    </ol>
                    @break

                @case('confirmo')
                    {{-- Booking: a day, its free times, and the SMS that confirms it. --}}
                    <div class="saas-book">
                        <div class="saas-book__days">
                            @foreach ([['Lu', 13], ['Ma', 14], ['Mi', 15], ['Jo', 16], ['Vi', 17]] as [$day, $date])
                                <span @class(['is-on' => $loop->iteration === 2])><small>{{ $day }}</small><b>{{ $date }}</b></span>
                            @endforeach
                        </div>
                        <div class="saas-book__slots">
                            @foreach (['09:00', '10:30', '12:00', '14:30', '16:00', '17:30'] as $time)
                                <span @class(['is-on' => $time === '14:30', 'is-taken' => $time === '12:00'])>{{ $time }}</span>
                            @endforeach
                        </div>
                        <p class="saas-book__sms"><x-software.icon name="message" /><span><b>SMS</b>Programarea ta la Salon Lila e confirmată: marți, 14:30.</span></p>
                    </div>
                    @break
            @endswitch
        </div>
    </div>
</div>
