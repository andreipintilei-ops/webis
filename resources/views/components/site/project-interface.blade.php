{{-- Decorative interface placeholders; no client screenshots or invented metrics. --}}
@props(['variant' => 'university'])

<div class="project-interface" aria-hidden="true">
    <div class="project-interface__chrome"><i></i><i></i><i></i><span></span></div>
    <div class="project-interface__workspace">
        <div class="project-interface__sidebar">
            <span class="project-interface__brand"></span>
            <span class="project-interface__nav project-interface__nav--active"></span>
            <span class="project-interface__nav"></span>
            <span class="project-interface__nav"></span>
            <span class="project-interface__nav"></span>
        </div>
        <div class="project-interface__main">
            <div class="project-interface__toolbar"><span></span><i></i></div>
            @if ($variant === 'company')
                <div class="project-interface__stats"><span></span><span></span><span></span></div>
                <div class="project-interface__chart">
                    @foreach ([35, 55, 45, 72, 62, 88, 76] as $height)
                        <span style="--bar-height: {{ $height }}%"></span>
                    @endforeach
                </div>
            @elseif ($variant === 'institution')
                <div class="project-interface__workflow">
                    @foreach (range(1, 3) as $column)
                        <div class="project-interface__lane">
                            <span class="project-interface__lane-title"></span>
                            @foreach (range(1, $column === 2 ? 3 : 2) as $card)
                                <div class="project-interface__ticket"><i></i><span></span><span></span></div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @else
                <div class="project-interface__stats"><span></span><span></span><span></span></div>
                <div class="project-interface__table">
                    @foreach (range(1, 4) as $row)
                        <div><i></i><span></span><span></span><b></b></div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
