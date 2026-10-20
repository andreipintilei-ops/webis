{{--
    /despre — who Webis is (css/site/about.css):
    - the internal pages' hero with the planes, its text and planes
      sinking back slower than the page, behind the hairline the figures
      start on (the hero clips them there: the figures run on under it on
      the same background);
    - the figures, on the hero's background (components/site/figures);
    - from projects to products: the title across, the text set off to the
      right under it, then a tall card per product on its own gradient —
      the name, its line and an arrow at the foot;
    - how we work: each principle with its own drawing, in the service
      cards' style (components/site/about-illustration), four in a row;
    then the footer's closing call, which is this page's call to action.

    [Bracketed] values are placeholders to fill in.
--}}
@php
    use App\Enums\Product;

    // Lucide icons.
    $lucide = [
        'flask' => '<path d="M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2"/><path d="M6.453 15h11.094"/><path d="M8.5 2h7"/>',
        'vote' => '<path d="m9 12 2 2 4-4"/><path d="M5 7c0-1.1.9-2 2-2h10a2 2 0 0 1 2 2v12H5V7Z"/><path d="M22 19H2"/>',
        'smile' => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/>',
        'calendar-check' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/>',
    ];
    $icon = fn (string $name) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$lucide[$name].'</svg>';

    $products = [
        [Product::Rims, 'Cercetare, finanțări și raportare pentru universități.', 'flask'],
        [Product::ParticipatoryBudgeting, 'Proiecte propuse și votate de cetățeni.', 'vote'],
        [Product::SmileSoft, '[o linie]', 'smile'],
        [Product::Confirmo, 'Programări și rezervări online.', 'calendar-check'],
    ];

    $principles = [
        ['team', 'Aceeași echipă de la început până la final.', 'Vorbești cu oamenii care îți construiesc proiectul, nu cu intermediari.'],
        ['steps', 'Lucrăm pe etape.', 'Vezi progresul pe parcurs și poți schimba direcția înainte să fie scump.'],
        ['plain', 'Explicăm fără jargon.', 'Știi mereu ce se face, de ce și cât durează.'],
        ['after', 'Rămânem și după lansare.', 'Mentenanță, actualizări și îmbunătățiri pe măsură ce afacerea ta crește.'],
    ];

@endphp

<x-layouts.public
    full-title="Despre Webis – firmă de software din Iași"
    description="Webis este o firmă de software din Iași. Din 2016 construim aplicații la comandă, produse SaaS, site-uri și magazine online."
    header-theme="dark"
>
    <x-site.page-hero
        title="Despre Webis."
        intro="Firmă de software din Iași. Din 2016 construim aplicații la comandă, produse SaaS, site-uri și magazine online."
        data-reveal-pace="fast"
    />

    {{-- ================================================== The figures --}}
    <x-site.figures />

    {{-- ================================================== From projects to products --}}
    <section class="about-cards" aria-labelledby="about-products-title" data-reveal-pace="fast">
        <div class="about-cards__inner">
            <header class="about-cards__head" data-reveal="head">
                <h2 id="about-products-title" class="about-cards__title">De la proiecte la <span>produse</span></h2>
                <p class="about-cards__text">Unele nevoi apar la mai multe organizații. Când vedem aceeași problemă de mai multe ori, o rezolvăm o singură dată, ca produs: testat, întreținut și îmbunătățit pe baza feedbackului celor care îl folosesc.</p>
            </header>

            <ul class="about-cards__list" data-reveal>
                @foreach ($products as [$product, $line, $glyph])
                    @php($external = $product->externalUrl() !== null)
                    <li data-reveal-child>
                        <a href="{{ $product->href() }}" class="about-card about-card--{{ $loop->iteration }}" @if ($external) target="_blank" rel="noopener" @endif>
                            <span class="about-card__icon" aria-hidden="true">{!! $icon($glyph) !!}</span>
                            <span class="about-card__foot">
                                <span class="about-card__name">{{ $product->label() }}</span>
                                <span class="about-card__line">{{ $line }}</span>
                            </span>
                            <span class="about-card__go" aria-hidden="true"><svg viewBox="0 0 11 10" fill="currentColor"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg></span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="about-cards__foot">
                <x-ui.arrow-link href="/produse">Vezi toate produsele</x-ui.arrow-link>
            </div>
        </div>
    </section>

    {{-- ================================================== How we work --}}
    <section class="chapter chapter--light about-how" aria-labelledby="about-how-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <h2 id="about-how-title" class="chapter__title about-how__title" data-reveal="head">Cum lucrăm</h2>

            <ul class="about-how__list" data-reveal>
                @foreach ($principles as [$art, $title, $line])
                    <li class="about-how__point" data-reveal-child>
                        <x-site.about-illustration :art="$art" :id="'how-'.$art" />
                        <h3 class="about-how__point-title">{{ $title }}</h3>
                        <p class="about-how__line">{{ $line }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</x-layouts.public>
