{{--
    FAQ block (App\Blocks\FaqBlock): as the questions on /web, /software-la-
    comanda and /produse (css/site/web.css): the title on the left with its
    line and the way to write to us, held in place while the questions
    scroll past on the right (js/public/split-heads.ts, from 1024px); the
    questions as rows that open, on the light chapter's lavender. The line
    is the block's intro, or the pages' own "Nu găsești răspunsul?".

    Each row is a native <details>, so it opens without JavaScript;
    js/public/project-accordion.ts animates it and keeps one open at a time
    (data-accordion-item, data-accordion-case). The +, turning into a ×, is
    css/site/web.css's.

    Answers are plain text (they also feed the FAQPage JSON-LD): escaped,
    a blank line between paragraphs, a single line break kept.
--}}
@php
    $items = array_values(array_filter(
        $data['items'] ?? [],
        fn ($item) => is_array($item) && trim((string) ($item['question'] ?? '')) !== '' && trim((string) ($item['answer'] ?? '')) !== '',
    ));
    $heading = trim((string) ($data['heading'] ?? '')) ?: 'Întrebări frecvente';
    $intro = trim((string) ($data['intro'] ?? '')) ?: 'Nu găsești răspunsul? Scrie-ne și îți răspundem cât de curând.';
@endphp

@if ($items !== [])
    <section id="{{ $block['id'] }}" class="chapter chapter--light web-faq" aria-labelledby="{{ $block['id'] }}-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <div class="web-split" data-split>
                <header class="web-split__head" data-split-head data-reveal="head">
                    <h2 id="{{ $block['id'] }}-title" class="chapter__title">{{ $heading }}</h2>
                    <p class="web-split__line">{{ $intro }}</p>
                    <x-ui.arrow-link href="/contact/">Scrie-ne</x-ui.arrow-link>
                </header>

                <div class="web-faq__list web-split__body" data-split-body data-reveal>
                    @foreach ($items as $item)
                        <details class="web-faq__item" data-accordion-item data-reveal-child>
                            <summary class="web-faq__question">
                                <span>{{ $item['question'] }}</span>
                                <span class="web-faq__icon" aria-hidden="true"></span>
                            </summary>
                            <div class="web-faq__answer" data-accordion-case>
                                @foreach (preg_split('/\R\s*\R/', trim($item['answer'])) ?: [] as $paragraph)
                                    <p>{!! nl2br(e(trim($paragraph)), false) !!}</p>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
