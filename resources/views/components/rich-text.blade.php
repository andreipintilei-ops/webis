{{--
    Rendered rich text (App\View\Components\RichText), styled as the site's
    reading text (css/site/block-rich-text.css): the sanitised HTML as it
    was saved, its image placeholders swapped for the media library's
    images (components/picture), each with its caption.

    `html`: the `{field}_html` that Block::prepare() stores. Images are
    shown at most at the text column's width.
--}}
<div {{ $attributes->class('rich-text') }}>
    @foreach ($parts as $part)
        @if (isset($part['asset']))
            <figure class="rt-image">
                <x-picture :asset="$part['asset']" sizes="(min-width: 768px) 46rem, 100vw" />
                @if ($part['caption'] !== '')
                    <figcaption>{!! $part['caption'] !!}</figcaption>
                @endif
            </figure>
        @else
            {!! $part['html'] !!}
        @endif
    @endforeach
</div>
