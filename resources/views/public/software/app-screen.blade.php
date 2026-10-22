{{--
    Software la comandă, 01 "Cu o aplicație": one calm screen — the requests
    in a list, each with its status. Decorative; sized in em off the box's
    width (css/site/software.css, .sw-screen).
--}}
@php
    $requests = [
        ['Cerere concediu', 'Ana P.', 'Aprobat', 'ok'],
        ['Referat achiziție', 'Mihai R.', 'În lucru', 'work'],
        ['Decont deplasare', 'Ioana D.', 'Așteaptă semnătura', 'wait'],
        ['Adeverință salariat', 'Radu T.', 'Aprobat', 'ok'],
    ];
@endphp

<div class="sw-screen" aria-hidden="true">
    <div class="sw-screen__bar">
        <span class="sw-screen__tile"><x-software.icon name="inbox" /></span>
        <b>Cereri</b>
        <span class="sw-screen__search"><x-software.icon name="search" />Caută</span>
    </div>
    <ul class="sw-screen__rows">
        @foreach ($requests as [$title, $person, $status, $tone])
            <li>
                <span class="sw-screen__no">#{{ 2381 - $loop->index }}</span>
                <span class="sw-screen__what"><b>{{ $title }}</b><small>{{ $person }}</small></span>
                <span class="sw-status sw-status--{{ $tone }}">{{ $status }}</span>
            </li>
        @endforeach
    </ul>
</div>
