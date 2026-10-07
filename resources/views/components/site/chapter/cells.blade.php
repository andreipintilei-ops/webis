{{--
    A chapter's grid of cells between hairlines — no boxes (css/site/
    service-chapters.css). `cells`: [{name, text, example?, href?}]; a cell
    with `href` is one link (its name). `columns`: 3 or 4 from 1024px.
--}}
@props(['cells' => [], 'columns' => 3])

<ul {{ $attributes->class(['chapter-cells', 'chapter-cells--'.$columns]) }}>
    @foreach ($cells as $cell)
        <li class="chapter-cell" style="--i: {{ $loop->index }}" data-reveal-child>
            <span class="chapter-cell__index">{{ sprintf('%02d', $loop->iteration) }}</span>
            <h4 class="chapter-cell__name">
                @if (! empty($cell['href']))
                    <a href="{{ $cell['href'] }}">{{ $cell['name'] }}</a>
                @else
                    {{ $cell['name'] }}
                @endif
            </h4>
            <p class="chapter-cell__text">{{ $cell['text'] }}</p>
            @if (! empty($cell['example']))
                <p class="chapter-cell__example">Exemplu: {{ $cell['example'] }}</p>
            @endif
        </li>
    @endforeach
</ul>
