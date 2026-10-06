{{--
    Section 8, Contact: the form and the ways to reach Webis, on the hero's
    navy so the page ends where it began.

    The form posts to ContactController (a lead in the admin's Cereri). It
    works without JavaScript (a normal post, back here with a thank-you);
    js/public/contact-form.ts sends it in place instead and shows errors by
    their fields. `organizație` tells whether it is a university, a town hall
    or a company — and so who answers.
--}}
@php
    $company = app(\App\Settings\CompanySettings::class);
    $phoneHref = $company->phone ? 'tel:'.preg_replace('/[^\d+]/', '', $company->phone) : null;
    $address = implode(', ', array_filter([$company->street_address, $company->locality]));
    $sent = session('contact-sent', false);

    $fields = [
        ['name' => 'name', 'label' => 'Nume', 'type' => 'text', 'autocomplete' => 'name', 'required' => true],
        ['name' => 'organization', 'label' => 'Organizație', 'type' => 'text', 'autocomplete' => 'organization', 'required' => false],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'autocomplete' => 'email', 'required' => false],
        ['name' => 'phone', 'label' => 'Telefon', 'type' => 'tel', 'autocomplete' => 'tel', 'required' => false],
    ];
@endphp

<section id="contact" class="contact" aria-labelledby="contact-title" data-nav-tone="dark">
    <div class="contact__inner">
        <div class="contact__aside">
            <h2 id="contact-title" class="contact__title">Hai să discutăm</h2>

            <ul class="contact__details">
                @if ($phoneHref)
                    <li><a href="{{ $phoneHref }}" class="contact__detail">{{ $company->phone }}</a></li>
                @endif
                @if ($company->email)
                    <li><a href="mailto:{{ $company->email }}" class="contact__detail">{{ $company->email }}</a></li>
                @endif
                @if ($address !== '')
                    <li class="contact__detail">{{ $address }}</li>
                @endif
            </ul>

            <p class="contact__promise">Răspundem în aceeași zi lucrătoare.</p>
        </div>

        <div class="contact__main">
            <p @class(['contact__sent', 'hidden' => ! $sent]) role="status" data-contact-sent>
                Mulțumim! Vă răspundem în aceeași zi lucrătoare.
            </p>

            <form method="POST" action="{{ route('contact.store') }}" @class(['contact__form', 'hidden' => $sent]) data-contact-form novalidate>
                @csrf

                <div class="contact__fields">
                    @foreach ($fields as $field)
                        <div class="contact__field">
                            <label for="contact-{{ $field['name'] }}" class="contact__label">{{ $field['label'] }}</label>
                            <input
                                id="contact-{{ $field['name'] }}"
                                name="{{ $field['name'] }}"
                                type="{{ $field['type'] }}"
                                value="{{ old($field['name']) }}"
                                autocomplete="{{ $field['autocomplete'] }}"
                                class="contact__input"
                                aria-describedby="contact-{{ $field['name'] }}-error"
                                @if ($field['required']) required @endif
                                @error($field['name']) aria-invalid="true" @enderror
                            >
                            <p id="contact-{{ $field['name'] }}-error" class="contact__error" data-error-for="{{ $field['name'] }}">@error($field['name']){{ $message }}@enderror</p>
                        </div>
                    @endforeach

                    <div class="contact__field contact__field--wide">
                        <label for="contact-message" class="contact__label">Ce aveți nevoie</label>
                        <textarea id="contact-message" name="message" rows="4" class="contact__input contact__input--area" aria-describedby="contact-message-error" required @error('message') aria-invalid="true" @enderror>{{ old('message') }}</textarea>
                        <p id="contact-message-error" class="contact__error" data-error-for="message">@error('message'){{ $message }}@enderror</p>
                    </div>

                    {{-- Honeypot: hidden from people and screen readers; bots fill it. --}}
                    <div class="contact__trap" aria-hidden="true">
                        <label for="contact-website">Website</label>
                        <input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>
                </div>

                <div class="contact__submit">
                    <x-ui.pill tone="dark" data-contact-submit>Trimite</x-ui.pill>
                    <p class="contact__privacy">
                        Datele trimise sunt folosite doar pentru a vă răspunde.
                        <a href="/confidentialitate">Confidențialitate</a>
                    </p>
                </div>

                <p class="contact__error contact__error--form" data-error-for="form" role="alert"></p>
            </form>
        </div>
    </div>
</section>
