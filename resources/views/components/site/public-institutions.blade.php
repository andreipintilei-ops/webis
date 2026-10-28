{{-- Section 5: what public institutions need to know about working with Webis. --}}
@php
    $commitments = [
        'Contractare și facturare conform cerințelor',
        'Mentenanță pe bază de contract',
        'Suport în limba română',
        'GDPR și găzduire în UE',
        'Documentație de implementare și predare',
    ];
@endphp

<section id="institutii-publice" class="public-institutions" aria-labelledby="public-institutions-title">
    <div class="public-institutions__inner">
        <div class="public-institutions__heading">
            <p class="public-institutions__eyebrow">Pentru instituții publice</p>
            <h2 id="public-institutions-title" class="public-institutions__title">
                Lucrăm cu instituții<br>
                din <span class="public-institutions__year">2016</span>
            </h2>
        </div>

        <ul class="public-institutions__list" role="list">
            @foreach ($commitments as $commitment)
                <li class="public-institutions__item">
                    <span class="public-institutions__check" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m5 12 4 4 10-10" />
                        </svg>
                    </span>
                    <span>{{ $commitment }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>
