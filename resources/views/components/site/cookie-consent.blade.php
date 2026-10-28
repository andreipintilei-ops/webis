{{--
    The cookie banner (Support\Consent\CookieConsent): a card at the
    screen's bottom left, until the visitor chooses. Accepting all and
    keeping only the necessary ones are buttons of the same weight, side
    by side; "Personalizează", beside the title, opens a switch for each
    category on offer, off until turned on — naming the tools set in the
    admin. Nothing optional is set before a choice.

    Hidden in the markup and shown by js/public/cookie-consent.ts when no
    current choice is saved, or from any [data-cookie-settings] button (the
    footer's "Setări cookies", the cookie policy). Not shown at all when no
    tracking tool is set in the admin. css/site/cookie-consent.css.
--}}
@php
    $consent = app(\App\Support\Consent\CookieConsent::class);
    $categories = $consent->categories();
    $uses = collect([
        $categories['analytics'] ? 'de analiză, ca să vedem ce pagini sunt utile' : null,
        $categories['marketing'] ? 'de marketing, ca să măsurăm reclamele' : null,
    ])->filter()->join(' și ');
    $choices = array_filter([
        'analytics' => $categories['analytics'] ? ['name' => 'Analiză', 'note' => 'Ce pagini sunt citite și de unde vin vizitatorii'] : null,
        'marketing' => $categories['marketing'] ? ['name' => 'Marketing', 'note' => 'Dacă reclamele noastre aduc cereri'] : null,
    ]);
@endphp

@if ($consent->needed())
    <section class="cookie-consent" role="dialog" aria-labelledby="cookie-consent-title" aria-describedby="cookie-consent-text" tabindex="-1" data-cookie-consent hidden>
        <div class="cookie-consent__head">
            <h2 id="cookie-consent-title" class="cookie-consent__title">Cookie-uri</h2>
            <button type="button" class="cookie-consent__more" aria-expanded="false" aria-controls="cookie-consent-choices" data-cookie-customise>
                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2 4.5h7M13 4.5h1M2 11.5h1M7 11.5h7" /><circle cx="11" cy="4.5" r="1.75" /><circle cx="5" cy="11.5" r="1.75" /></svg>
                Personalizează
            </button>
        </div>
        <p id="cookie-consent-text" class="cookie-consent__text">
            Folosim cookie-urile necesare ca site-ul să funcționeze. Cu acordul tău, folosim și cookie-uri {{ $uses }}.
            Îți poți schimba alegerea oricând. Detalii în <a href="{{ \App\Support\TrailingSlash::href('/politica-de-cookies') }}">Politica de cookies</a>.
        </p>

        <div id="cookie-consent-choices" class="cookie-consent__choices" data-cookie-choices hidden>
            <div class="cookie-consent__choice">
                <span class="cookie-consent__choice-text">
                    <span class="cookie-consent__choice-name">Necesare</span>
                    <span class="cookie-consent__choice-note">Sesiunea, securitatea formularelor, alegerea ta.</span>
                </span>
                <span class="cookie-consent__always">Mereu active</span>
            </div>
            @foreach ($choices as $category => $choice)
                <label class="cookie-consent__choice">
                    <span class="cookie-consent__choice-text">
                        <span class="cookie-consent__choice-name">{{ $choice['name'] }}</span>
                        <span class="cookie-consent__choice-note">{{ $choice['note'] }}: {{ implode(', ', $consent->names($category)) }}.</span>
                    </span>
                    <input type="checkbox" role="switch" class="cookie-consent__switch" name="{{ $category }}" data-cookie-category="{{ $category }}">
                </label>
            @endforeach
        </div>

        <div class="cookie-consent__actions">
            {{-- With the switches open: saving them takes the place of "Doar necesare". --}}
            <x-ui.pill type="button" class="cookie-consent__button" data-cookie-save hidden>Salvează alegerea</x-ui.pill>
            <x-ui.pill type="button" class="cookie-consent__button" data-cookie-accept>Accept toate</x-ui.pill>
            <x-ui.pill type="button" class="cookie-consent__button" data-cookie-reject>Doar necesare</x-ui.pill>
        </div>
    </section>
@endif
