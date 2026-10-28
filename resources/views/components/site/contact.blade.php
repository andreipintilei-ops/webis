{{--
    Section 8, Contact: the form and the ways to reach Webis, on the hero's
    navy so the page ends where it began.

    The form posts to ContactController (a lead in the admin's Cereri). It
    works without JavaScript (a normal post, back here with a thank-you);
    js/public/contact-form.ts sends it in place instead and shows errors by
    their fields. `organizație` tells whether it is a university, a town hall
    or a company — and so who answers.

    On the contact page, ?produs={product} (Produse SaaS's "Cere o
    prezentare") starts the message as a demo request for that product;
    ?cerere=prezentare (the footer's call there) as one for a product the
    visitor names.

    The quote-form block (blocks/quote-form) sets its own `title` and
    `intro`, and may leave out the phone, email and address (`details`).
    Other attributes go on the section.
--}}
@props(['page' => false, 'title' => null, 'intro' => null, 'details' => true])

@php
    $company = app(\App\Settings\CompanySettings::class);
    $phoneHref = \App\Support\PhoneLink::href($company->phone);
    $address = implode(', ', array_filter([$company->street_address, $company->locality]));
    // The map (the contact page): where the office is, from the address;
    // the Google place behind the reviews link, when there is one, so
    // "open in Google Maps" lands on the business itself, not just the
    // street.
    parse_str((string) parse_url((string) $company->google_reviews_url, PHP_URL_QUERY), $reviewsQuery);
    $mapQuery = implode(', ', array_filter([$company->name, $company->street_address, $company->locality]));
    $mapOpen = 'https://www.google.com/maps/search/?'.http_build_query(array_filter(['api' => 1, 'query' => $mapQuery, 'query_place_id' => $reviewsQuery['placeid'] ?? null]));
    $mapEmbed = 'https://maps.google.com/maps?'.http_build_query(['q' => $mapQuery, 'z' => 16, 'hl' => 'ro', 'output' => 'embed']);
    $product = $page ? \App\Enums\Product::tryFrom((string) request()->query('produs')) : null;
    // ?cerere=prezentare (the footer's call on Produse SaaS): a demo, the
    // product left for the visitor to name.
    $message = match (true) {
        $product !== null => 'Aș vrea o prezentare pentru '.$product->label().'.',
        $page && request()->query('cerere') === 'prezentare' => 'Aș dori o prezentare pentru produsul: ',
        default => '',
    };
    $sent = session('contact-sent', false);

    $fields = [
        ['name' => 'name', 'label' => 'Nume', 'type' => 'text', 'autocomplete' => 'name', 'required' => true],
        ['name' => 'organization', 'label' => 'Firmă sau instituție', 'type' => 'text', 'autocomplete' => 'organization', 'required' => false],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'autocomplete' => 'email', 'required' => false],
        ['name' => 'phone', 'label' => 'Telefon', 'type' => 'tel', 'autocomplete' => 'tel', 'required' => false],
    ];
@endphp

<section id="contact" {{ $attributes->class(['contact', 'chapter contact-page' => $page]) }} aria-labelledby="contact-title" @if ($page) data-on-backdrop data-backdrop-area @endif data-nav-tone="dark">
    <div class="contact__inner">
        <div class="contact__aside">
            @if ($page)
                <p class="chapter__label">Un proiect începe cu o conversație</p>
                <h2 id="contact-title" class="chapter__title">Spune-ne ce<br>vrei să construim.</h2>
                <p class="contact-page__intro">Ai o idee, un proces de simplificat sau un sistem care nu mai ține pasul? Povestește-ne despre el.</p>
            @else
                <h2 id="contact-title" class="contact__title">{{ $title ?? 'Hai să discutăm' }}</h2>
                @if ($intro)
                    <p class="contact__intro">{{ $intro }}</p>
                @endif
            @endif

            @if ($details)
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
            @endif

            <p class="contact__promise">Răspundem în aceeași zi lucrătoare.</p>
        </div>

        <div class="contact__main">
            <p @class(['contact__sent', 'hidden' => ! $sent]) role="status" tabindex="-1" data-contact-sent>
                Mulțumim! Îți răspundem în aceeași zi lucrătoare.
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
                        <label for="contact-message" class="contact__label">{{ $page ? 'Ce ai în minte?' : 'De ce ai nevoie?' }}@if ($page) <span aria-hidden="true">*</span>@endif</label>
                        <textarea id="contact-message" name="message" rows="4" maxlength="5000" @if ($page) placeholder="De exemplu: ce vrei să construiești sau să îmbunătățești și pentru cine." @endif class="contact__input contact__input--area" aria-describedby="contact-message-error" required @error('message') aria-invalid="true" @enderror>{{ old('message', $message) }}</textarea>
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
                        <a href="/politica-de-confidentialitate/">Confidențialitate</a>
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

        {{-- Where we are: Google's map, always there — loaded only as the
             page is scrolled near it (loading="lazy"), so it costs the
             first screen nothing. --}}
        @if ($address !== '')
            <div class="contact-page__map">
                <div class="chapter-sub__header">
                    <h2 class="chapter-sub__label">Unde ne găsești</h2>
                    <a href="{{ $mapOpen }}" class="contact-map__open" target="_blank" rel="noopener">Deschide în Google Maps</a>
                </div>
                <div class="contact-map">
                    <iframe src="{{ $mapEmbed }}" title="Harta: {{ $mapQuery }}" class="contact-map__frame" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>
            </div>
        @endif
    @endif
</section>
