{{--
    FAQ block (App\Blocks\FaqBlock): questions as rows that open, as on /web
    (06, "Întrebări frecvente"), under the site's numbered section heading,
    on the light chapter's lavender.

    Each row is a native <details>, so it opens without JavaScript;
    js/public/project-accordion.ts animates it and keeps one open at a time
    (data-accordion-item, data-accordion-case). The +, turning into a ×, is
    css/site/block-faq.css.

    Answers are plain text (they also feed the FAQPage JSON-LD): escaped,
    a blank line between paragraphs, a single line break kept.
--}}
@php
    $items = array_values(array_filter(
        $data['items'] ?? [],
        fn ($item) => is_array($item) && trim((string) ($item['question'] ?? '')) !== '' && trim((string) ($item['answer'] ?? '')) !== '',
    ));
    $heading = trim((string) ($data['heading'] ?? ''));
@endphp

@if ($items !== [])
    <section id="{{ $block['id'] }}" class="chapter chapter--light block-faq" @if ($heading !== '') aria-labelledby="{{ $block['id'] }}-title" @endif data-reveal-pace="fast">
        <div class="chapter__inner">
            @if ($heading !== '')
                @php $number = isset($sectionNumber) ? $sectionNumber() : ''; @endphp
                <x-ui.section-head :number="$number" :title="$heading" :id="$block['id'].'-title'" />
            @endif

            <div class="block-faq__list" data-reveal>
                @foreach ($items as $item)
                    <details class="block-faq__item" data-accordion-item data-reveal-child>
                        <summary class="block-faq__question">
                            <span>{{ $item['question'] }}</span>
                            <span class="block-faq__icon" aria-hidden="true"></span>
                        </summary>
                        <div class="block-faq__answer" data-accordion-case>
                            @foreach (preg_split('/\R\s*\R/', trim($item['answer'])) ?: [] as $paragraph)
                                <p>{!! nl2br(e(trim($paragraph)), false) !!}</p>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endif
