{{--
    The reviews, on the home page after the service chapters: a head (the
    title, the Google rating under it, centred), then the reviews as
    published on webis.ro, as a carousel (js/public/reviews-carousel.ts;
    css/site/reviews-carousel.css): one in focus, the neighbours smaller on
    either side, no buttons; swipe, drag, a click on a side one or (the
    carousel in focus) the arrow keys move it, the cards growing and
    shrinking as they go. Each text is cut to the same height; "Citește tot"
    opens the one in focus downward. Without the
    script: the reviews side by side, whole. In the chapters' light colours
    and entry (css/site/service-chapters.css; js/public/reveals.ts).
--}}
<section id="recenzii" class="site-reviews chapter chapter--light" aria-labelledby="reviews-title">
    <div class="chapter__inner">
        <header class="chapter__head site-reviews__head" data-reveal="head">
            <h2 id="reviews-title" class="chapter__title">În <span class="whitespace-nowrap">cuvintele <x-ui.title-pill variant="reviews" /></span> <span class="whitespace-nowrap">clienților.</span></h2>
            <x-site.google-rating tone="light" class="chapter-rating site-reviews__rating" />
        </header>

        <div class="reviews-carousel" role="region" aria-roledescription="carusel" aria-label="Recenziile clienților (săgețile stânga și dreapta le schimbă)" tabindex="0" data-reveal data-reviews-carousel>
            {{-- As published on webis.ro, word for word. --}}
            <div id="recenzii-lista" class="chapter-reviews reviews-carousel__track" data-reviews-track>
                @foreach ([
                    ['Florin Pop', 'familytravel.ro', 'Alex de la Webis este un profeionist si un prieten. Pe noi cei de la Family Travel ne-a ajutat foarte mult prin implementarea de legaturi XML cu baze de date de la partenerii nostri. Ce m-a impresionat la el este ca nu stie sa zica "nu se poate". Pentru fiecare problema are o solutie. Pe langa relatia profesionala ii place sa cunoasca si omul din spatele site-ului si astfel isi da seama mai bine de nevoile fiecaruia. Recomand cu drag!', 'familytravel.webp'],
                    ['Bogdan Bucovanu', 'consultantmedical.ro', 'Companie web design foarte serioasa, Beniamin este băiatul bun la toate care a avut răbdarea sa îmi explice ce face fiecare funcție, cum lucram in spatele site-ului ca si arministrator. Întreaga echipa a dat viata unei idei personale și a adus un mare plus la dezvoltarea site-ului consultantmedical.ro Felicitări pentru seriozitate și profesionalism!', 'consultantmedical.webp'],
                    ['Alexandra Petcu', 'zyanya.ro', 'Colaborarea cu Webis a fost una excepțională! Echipa a demonstrat un nivel înalt de profesionalism de la început până la final. Am fost profund impresionata de atenția lor la detalii și de abilitatea lor de a transforma viziunea mea într-un site web captivant și funcțional.', 'zyanya.webp'],
                ] as [$name, $site, $text, $logo])
                    <div id="recenzie-{{ $loop->iteration }}" class="chapter-review" role="group" aria-roledescription="recenzie" aria-label="{{ $loop->iteration }} din {{ $loop->count }}" data-review>
                        {{-- The client's logo, as on webis.ro (public/images/clients). --}}
                        <img src="{{ asset('images/clients/'.$logo) }}" alt="" class="chapter-review__logo" width="80" height="80" loading="lazy" decoding="async">
                        <blockquote id="recenzie-{{ $loop->iteration }}-text" class="chapter-review__quote" data-review-quote><p>„{{ $text }}”</p></blockquote>
                        <button type="button" class="chapter-review__more" aria-expanded="false" aria-controls="recenzie-{{ $loop->iteration }}-text" hidden data-review-more><span data-review-more-label>Citește tot</span><svg viewBox="0 0 12 12" aria-hidden="true"><path d="M2 4.5 6 8.5l4-4" /></svg></button>
                        <p class="chapter-review__author"><cite>{{ $name }}</cite>, {{ $site }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
