{{--
    A chapter's grid of cells between hairlines — no boxes (css/site/
    service-chapters.css). `cells`: [{name, text, example?, href?}]; a cell
    with `href` is one link (its name). `columns`: 3 or 4 from 1024px.
    `numbered`: false for cells that are not a sequence. Add the class
    `chapter-cells--flush` to start the first column on the title's edge.
--}}
@props(['cells' => [], 'columns' => 3, 'numbered' => true])

<ul {{ $attributes->class(['chapter-cells', 'chapter-cells--'.$columns]) }}>
    @foreach ($cells as $cell)
        <li class="chapter-cell" style="--i: {{ $loop->index }}" data-reveal-child>
            @if ($numbered)
                <span class="chapter-cell__index">{{ sprintf('%02d', $loop->iteration) }}</span>
            @endif
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
