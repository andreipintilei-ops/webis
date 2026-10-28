{{--
    Video block (App\Blocks\VideoBlock): a YouTube video as a lite embed, on
    the light chapter's lavender; css/site/block-video.css.

    Until it is played, nothing of YouTube's player loads (nor its cookies):
    a 16:9 poster — the block's own image (components/picture), else
    YouTube's thumbnail — with the violet play button of the site's card
    (.post-card__button) in its middle. js/public/lite-video.ts swaps in the
    youtube-nocookie.com player on click, playing. Without JavaScript the
    button is a plain link to the video on YouTube.

    Under it, the title (also the button's accessible name) and the caption.
--}}
@php
    $id = $data['youtube_id'] ?? null;
    $title = trim((string) ($data['title'] ?? ''));
    $poster = isset($data['poster_asset_id']) ? $assets->get($data['poster_asset_id']) : null;
    $poster = $poster?->file() !== null ? $poster : null;
    $caption = trim((string) ($data['caption'] ?? ''));
@endphp

@if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1)
    <section id="{{ $block['id'] }}" class="chapter chapter--light block-video" data-reveal-pace="fast">
        <div class="chapter__inner">
            <figure class="block-video__figure" data-reveal>
                <div class="block-video__player" data-lite-video="{{ $id }}" data-title="{{ $title }}">
                    @if ($poster)
                        <x-picture :asset="$poster" alt="" sizes="(min-width: 1344px) 84rem, 100vw" :lcp="$isFirst ?? false" class="block-video__poster" />
                    @else
                        <img src="https://i.ytimg.com/vi/{{ $id }}/hqdefault.jpg" alt="" width="480" height="360" loading="lazy" decoding="async" class="block-video__poster">
                    @endif

                    <a href="https://www.youtube.com/watch?v={{ $id }}" class="block-video__play" data-lite-video-play aria-label="Redă videoclipul: {{ $title }}">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72a1 1 0 0 0 1.52.85l11.02-6.86a1 1 0 0 0 0-1.7L9.52 4.29A1 1 0 0 0 8 5.14Z" /></svg>
                    </a>
                </div>

                @if ($title !== '' || $caption !== '')
                    <figcaption class="block-video__caption">
                        @if ($title !== '')
                            <span class="block-video__title">{{ $title }}</span>
                        @endif
                        @if ($caption !== '')
                            <span class="block-video__line">{{ $caption }}</span>
                        @endif
                    </figcaption>
                @endif
            </figure>
        </div>
    </section>
@endif
