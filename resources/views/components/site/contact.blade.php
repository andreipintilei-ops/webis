{{--
    Section 8, Contact: the form and the ways to reach Webis, on the hero's
    navy so the page ends where it began.

    The form posts to ContactController (a lead in the admin's Cereri). It
    works without JavaScript (a normal post, back here with a thank-you);
    js/public/contact-form.ts sends it in place instead and shows errors by
    their fields. `organizație` tells whether it is a university, a town hall
    or a company — and so who answers.
--}}
@props(['page' => false])

@php
    $company = app(\App\Settings\CompanySettings::class);
    $phoneHref = \App\Support\PhoneLink::href($company->phone);
    $address = implode(', ', array_filter([$company->street_address, $company->locality]));
    $sent = session('contact-sent', false);

    $fields = [
        ['name' => 'name', 'label' => 'Nume', 'type' => 'text', 'autocomplete' => 'name', 'required' => true],
        ['name' => 'organization', 'label' => 'Organizație', 'type' => 'text', 'autocomplete' => 'organization', 'required' => false],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'autocomplete' => 'email', 'required' => false],
        ['name' => 'phone', 'label' => 'Telefon', 'type' => 'tel', 'autocomplete' => 'tel', 'required' => false],
    ];
@endphp

<section id="contact" @class(['contact', 'chapter contact-page' => $page]) aria-labelledby="contact-title" @if ($page) data-on-backdrop data-backdrop-area @endif data-nav-tone="dark">
    <div class="contact__inner">
        <div class="contact__aside">
            @if ($page)
                <p class="chapter__label">Un proiect începe cu o conversație</p>
                <h2 id="contact-title" class="chapter__title">Spune-ne ce<br>vrei să construim.</h2>
                <p class="contact-page__intro">Ai o idee, un proces de simplificat sau un sistem care nu mai ține pasul? Povestește-ne despre el.</p>
            @else
                <h2 id="contact-title" class="contact__title">Hai să discutăm</h2>
            @endif

            <ul class="contact__details">
                @if ($phoneHref)
                    <li>
                        @if ($page)<span class="contact-page__detail-label">Sună-ne</span>@endif
                        <a href="{{ $phoneHref }}" class="contact__detail">{{ $company->phone }}</a>
                    </li>
                @endif
                @if ($company->email)
                    <li>
                        @if ($page)<span class="contact-page__detail-label">Scrie-ne</span>@endif
                        <a href="mailto:{{ $company->email }}" class="contact__detail">{{ $company->email }}</a>
                    </li>
                @endif
                @if ($address !== '')
                    <li>
                        @if ($page)<span class="contact-page__detail-label">Unde ne găsești</span>@endif
                        <address class="contact__detail not-italic">{{ $address }}</address>
                    </li>
                @endif
            </ul>

            <p class="contact__promise">Răspundem în aceeași zi lucrătoare.</p>
        </div>

        <div class="contact__main">
            <p @class(['contact__sent', 'hidden' => ! $sent]) role="status" tabindex="-1" data-contact-sent>
                Mulțumim! Vă răspundem în aceeași zi lucrătoare.
            </p>

            <form method="POST" action="{{ route('contact.store') }}" @class(['contact__form', 'hidden' => $sent]) data-contact-form novalidate>
                @csrf

                @if ($page)
                    <div class="contact-page__form-head">
                        <h3>Despre proiectul tău</h3>
                        <p>Câmpurile marcate cu * sunt obligatorii. Lasă-ne un email sau un număr de telefon ca să îți putem răspunde.</p>
                    </div>
                @endif

                <div class="contact__fields">
                    @foreach ($fields as $field)
                        <div class="contact__field">
                            <label for="contact-{{ $field['name'] }}" class="contact__label">{{ $field['label'] }}@if ($page && $field['required']) <span aria-hidden="true">*</span>@endif</label>
                            <input
                                id="contact-{{ $field['name'] }}"
                                name="{{ $field['name'] }}"
                                type="{{ $field['type'] }}"
                                value="{{ old($field['name']) }}"
                                autocomplete="{{ $field['autocomplete'] }}"
                                maxlength="{{ ['name' => 120, 'organization' => 160, 'email' => 190, 'phone' => 40][$field['name']] }}"
                                class="contact__input"
                                aria-describedby="contact-{{ $field['name'] }}-error"
                                @if ($field['required']) required @endif
                                @error($field['name']) aria-invalid="true" @enderror
                            >
                            <p id="contact-{{ $field['name'] }}-error" class="contact__error" data-error-for="{{ $field['name'] }}">@error($field['name']){{ $message }}@enderror</p>
                        </div>
                    @endforeach

                    <div class="contact__field contact__field--wide">
                        <label for="contact-message" class="contact__label">{{ $page ? 'Ce ai în minte?' : 'Ce aveți nevoie' }}@if ($page) <span aria-hidden="true">*</span>@endif</label>
                        <textarea id="contact-message" name="message" rows="4" maxlength="5000" @if ($page) placeholder="De exemplu: ce vrei să construiești sau să îmbunătățești și pentru cine." @endif class="contact__input contact__input--area" aria-describedby="contact-message-error" required @error('message') aria-invalid="true" @enderror>{{ old('message') }}</textarea>
                        <p id="contact-message-error" class="contact__error" data-error-for="message">@error('message'){{ $message }}@enderror</p>
                    </div>

                    {{-- Honeypot: hidden from people and screen readers; bots fill it. --}}
                    <div class="contact__trap" aria-hidden="true">
                        <label for="contact-website">Website</label>
                        <input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>
                </div>

                <div class="contact__submit">
                    <x-ui.pill tone="dark" data-contact-submit>{{ $page ? 'Trimite mesajul' : 'Trimite' }}</x-ui.pill>
                    <p class="contact__privacy">
                        Folosim datele doar ca să-ți răspundem.
                        <a href="/confidentialitate">Confidențialitate</a>
                    </p>
                </div>

                <p class="contact__error contact__error--form" data-error-for="form" role="alert"></p>
            </form>
        </div>
    </div>
    @if ($page)
        <div class="contact-page__next">
            <div class="chapter-sub__header">
                <h2 class="chapter-sub__label">Ce urmează după mesaj</h2>
            </div>
            <ol class="chapter-cells chapter-cells--3">
                <li class="chapter-cell">
                    <span class="chapter-cell__index">01</span>
                    <h3 class="chapter-cell__name">Înțelegem contextul</h3>
                    <p class="chapter-cell__text">Citim mesajul și revenim cu întrebările care ne ajută să înțelegem de ce ai nevoie.</p>
                </li>
                <li class="chapter-cell">
                    <span class="chapter-cell__index">02</span>
                    <h3 class="chapter-cell__name">Discutăm împreună</h3>
                    <p class="chapter-cell__text">Vorbim despre cum lucrezi acum, ce vrei să schimbi și ce ar însemna un rezultat bun.</p>
                </li>
                <li class="chapter-cell">
                    <span class="chapter-cell__index">03</span>
                    <h3 class="chapter-cell__name">Stabilim pașii</h3>
                    <p class="chapter-cell__text">Îți propunem o direcție clară și stabilim împreună cum putem începe.</p>
                </li>
            </ol>
        </div>
    @endif
</section>
