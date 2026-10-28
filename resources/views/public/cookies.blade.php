{{--
    /politica-de-cookies/ (routes/web.php): the cookie policy. In code
    rather than the CMS so that its tables are the cookies the site really
    sets — Support\Consent\CookieConsent::table(), the same list the banner
    asks about; an optional category shows only while its tool is set in
    the admin.

    Laid out as an imported legal page: the internal pages' hero, then the
    text on the light chapter with its contents rail (blocks/rich-text,
    css/site/block-rich-text.css, js/public/rich-text-toc.ts).

    Change $updated with any change to the text or the tables.
--}}
@php
    $consent = app(\App\Support\Consent\CookieConsent::class);
    $groups = $consent->table();
    $optional = $consent->needed();
    $providers = $consent->providers();
    $company = app(\App\Settings\CompanySettings::class);
    $updated = '26 octombrie 2026';

    $sections = [
        'ce-sunt' => 'Ce sunt cookie-urile',
        'ce-folosim' => 'Ce cookie-uri folosim',
        'alte-site-uri' => 'Conținut de pe alte site-uri',
        'acordul-tau' => 'Cum îți dai sau retragi acordul',
        'drepturi' => 'Drepturile tale și contact',
        'modificari' => 'Modificări',
    ];
    $address = collect([$company->street_address, $company->locality, $company->region])->filter()->unique()->join(', ');
    $operator = collect([$company->legal_name, $company->vat_id ? 'CUI '.$company->vat_id : null, $address ?: null])->filter()->unique()->join(', ');
@endphp

<x-layouts.public
    title="Politica de cookies"
    description="Ce cookie-uri folosește webis.ro, de ce și cât timp, ce pornește doar cu acordul tău și cum îți schimbi alegerea oricând."
    crumb="Politica de cookies"
    header-theme="dark"
>
    <x-site.page-hero
        label="Informații legale"
        :title="['Politica de cookies.']"
        intro="Ce cookie-uri folosim, de ce, cât timp le păstrăm și cum îți schimbi alegerea oricând."
    />

    <section class="chapter chapter--light block-rich-text block-rich-text--rail" data-toc>
        <div class="block-rich-text__grid">
            <nav class="block-rich-text__rail" aria-labelledby="cookies-cuprins">
                <div class="block-rich-text__toc" data-toc-pin>
                    <p id="cookies-cuprins" class="section-head__number block-rich-text__label">Cuprins</p>
                    <ol class="block-rich-text__links">
                        @foreach ($sections as $id => $heading)
                            <li><a href="#{{ $id }}" class="block-rich-text__link" data-toc-link>{{ $heading }}</a></li>
                        @endforeach
                    </ol>
                </div>
            </nav>

            <div class="rich-text block-rich-text__body" data-toc-text>
                <p>
                    Această politică explică ce cookie-uri folosește site-ul {{ parse_url(config('site.canonical_url'), PHP_URL_HOST) }},
                    în ce scop, cât timp rămân în browserul tău și cum îți dai sau îți retragi acordul.
                    Site-ul este operat de {{ $operator }}.
                </p>
                <p><em>Ultima actualizare: {{ $updated }}.</em></p>

                <h2 id="ce-sunt">{{ $sections['ce-sunt'] }}</h2>
                <p>
                    Cookie-urile sunt fișiere mici de text pe care un site le salvează în browserul tău și le citește la vizitele următoare.
                    Unele sunt necesare ca site-ul să funcționeze. Altele ne ajută să înțelegem cum e folosit sau să măsurăm reclamele;
                    pe acestea le punem doar cu acordul tău, conform Legii nr. 506/2004 și Regulamentului (UE) 2016/679 (GDPR).
                </p>

                <h2 id="ce-folosim">{{ $sections['ce-folosim'] }}</h2>
                @unless ($optional)
                    <p>În prezent folosim doar cookie-urile necesare de mai jos. Nu folosim cookie-uri de analiză sau de marketing.</p>
                @endunless

                @foreach ($groups as $group)
                    <h3>{{ $group['title'] }}</h3>
                    <p>{{ $group['intro'] }}</p>
                    <table class="cookie-table">
                        <thead>
                            <tr>
                                <th scope="col">Cookie</th>
                                <th scope="col">Furnizor</th>
                                <th scope="col">Scop</th>
                                <th scope="col">Durată</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['rows'] as $row)
                                <tr>
                                    <td data-label="Cookie"><code>{{ $row['name'] }}</code></td>
                                    <td data-label="Furnizor">{{ $row['provider'] }}</td>
                                    <td data-label="Scop">{{ $row['purpose'] }}</td>
                                    <td data-label="Durată">{{ $row['duration'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach

                <h3>Preferințe păstrate în browser</h3>
                <p>
                    Pe lângă cookie-uri, site-ul ține în memoria browserului (localStorage, sessionStorage) două lucruri fără date personale:
                    modul de afișare ales pe pagina Portofoliu și, pentru o clipă, faptul că treci de la o pagină la alta, ca tranziția să se vadă corect.
                    Ele nu părăsesc dispozitivul tău și nu sunt trimise nimănui.
                </p>

                <h2 id="alte-site-uri">{{ $sections['alte-site-uri'] }}</h2>
                <p>
                    Unele pagini au videoclipuri de pe YouTube. Până nu apeși pe redare se încarcă cel mult imaginea de previzualizare (de pe i.ytimg.com, fără cookie-uri); playerul YouTube nu se încarcă.
                    Când pornești un videoclip, el se încarcă din modul de confidențialitate sporită al YouTube (youtube-nocookie.com), iar de atunci
                    YouTube (Google) poate salva date în browserul tău, după <a href="https://policies.google.com/privacy" rel="noopener">politica Google</a>.
                </p>
                <p>
                    Pagina de contact are harta Google Maps a adresei noastre. Ea se încarcă de la Google când ajungi în dreptul ei, iar de atunci
                    Google poate salva date în browserul tău, după <a href="https://policies.google.com/privacy" rel="noopener">politica Google</a>.
                </p>
                <p>Linkurile către rețelele sociale (Facebook, Instagram, LinkedIn) sunt simple linkuri: nu încarcă nimic de la acestea până nu le deschizi.</p>
                @if ($providers !== [])
                    <p>Instrumentele de analiză și de marketing, când le accepți, sunt furnizate de:</p>
                    <ul>
                        @foreach ($providers as $provider)
                            <li>{{ $provider['name'] }} ({{ $provider['tools'] }}), <a href="{{ $provider['url'] }}" rel="noopener">politica de confidențialitate</a>;</li>
                        @endforeach
                    </ul>
                    <p>Unele date pot fi transferate și în Statele Unite, în baza cadrului UE–SUA privind protecția datelor (Data Privacy Framework) sau a clauzelor contractuale standard ale Comisiei Europene.</p>
                @endif

                <h2 id="acordul-tau">{{ $sections['acordul-tau'] }}</h2>
                @if ($optional)
                    <p>
                        La prima vizită te întrebăm ce cookie-uri accepți. Poți accepta toate, poți păstra doar cele necesare sau poți alege pe categorii.
                        Până alegi, nu punem nimic în afara celor necesare, iar instrumentele de analiză și de marketing nici nu se încarcă.
                        Alegerea ta e păstrată {{ \App\Support\Consent\CookieConsent::DAYS }} de zile; după aceea te întrebăm din nou.
                    </p>
                    <p>Îți poți schimba alegerea oricând, de aici sau din linkul „Setări cookies” din subsolul fiecărei pagini. Retragerea acordului nu afectează ce s-a făcut înainte cu acordul dat.</p>
                    <p><x-ui.pill type="button" data-cookie-settings>Schimbă setările cookie</x-ui.pill></p>
                @else
                    <p>Cookie-urile necesare nu cer acord. Dacă vom folosi vreodată cookie-uri de analiză sau de marketing, te vom întreba înainte să le punem.</p>
                @endif
                <p>
                    Poți șterge sau bloca oricând cookie-urile și din setările browserului (de obicei la Setări › Confidențialitate).
                    Dacă blochezi cookie-urile necesare, unele părți ale site-ului, cum e formularul de contact, pot să nu mai funcționeze.
                </p>

                <h2 id="drepturi">{{ $sections['drepturi'] }}</h2>
                <p>
                    Cum prelucrăm datele personale și ce drepturi ai (acces, rectificare, ștergere, opoziție și celelalte) găsești în
                    <a href="{{ \App\Support\TrailingSlash::href('/politica-de-confidentialitate') }}">Politica de confidențialitate</a>.
                    @if ($company->email)
                        Pentru orice întrebare despre cookie-uri ne scrii la <a href="mailto:{{ $company->email }}">{{ $company->email }}</a>.
                    @endif
                </p>
                <p>Ai și dreptul să depui o plângere la Autoritatea Națională de Supraveghere a Prelucrării Datelor cu Caracter Personal (<a href="https://www.dataprotection.ro" rel="noopener">dataprotection.ro</a>).</p>

                <h2 id="modificari">{{ $sections['modificari'] }}</h2>
                <p>
                    Actualizăm această pagină când adăugăm sau scoatem un instrument. Dacă apare o categorie nouă de cookie-uri,
                    te întrebăm din nou înainte să o folosim.
                </p>
            </div>
        </div>
    </section>
</x-layouts.public>
