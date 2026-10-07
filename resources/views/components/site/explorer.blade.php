{{--
    An explorer of what a chapter builds (components/site/service-chapters):
    a list of types beside a stage. From 1024px the list is vertical tabs —
    hovering a row previews it, a click or Enter selects it, the arrows,
    Home and End move between rows — and the stage shows the active type:
    its description, then its project (a card, one link) or, without one,
    its typical modules and a link to talk. Below 1024px it is an accordion,
    each row opening in place, one at a time. js/public/explorer.ts sets the
    roles for each mode; css/site/explorer.css. Without the script every
    type is simply listed with its content.

    `id`: unique on the page. `items`: [{name, text, example?, link?:
    {label, href}, project?: {type, title, line, visual, metric?: [number,
    label], href}, modules?: list}].
--}}
@props(['id', 'items' => []])

<div id="{{ $id }}" {{ $attributes->class(['explorer']) }} style="--n: {{ count($items) }}" data-explorer>
    @foreach ($items as $index => $item)
        @php($key = $id.'-'.($index + 1))
        <div @class(['explorer-item', 'is-active' => $loop->first])>
            <button type="button" id="{{ $key }}-tab" class="explorer-row" aria-controls="{{ $key }}-panel" data-explorer-row>
                <span class="explorer-row__index">{{ sprintf('%02d', $loop->iteration) }}</span>
                <span class="explorer-row__name">{{ $item['name'] }}</span>
                @if (! empty($item['project']))
                    <span class="explorer-row__tag">1 proiect</span>
                @endif
                <span class="explorer-row__icon" aria-hidden="true"></span>
            </button>

            <div id="{{ $key }}-panel" class="explorer-panel" aria-labelledby="{{ $key }}-tab" data-explorer-panel>
                <div class="explorer-panel__body">
                    <p class="explorer-panel__text">{{ $item['text'] }}</p>
                    @if (! empty($item['example']))
                        <p class="explorer-panel__example">Exemplu: {{ $item['example'] }}</p>
                    @endif
                    @if (! empty($item['link']))
                        <x-ui.arrow-link :href="$item['link']['href']" class="explorer-panel__link">{{ $item['link']['label'] }}</x-ui.arrow-link>
                    @endif

                    @if (! empty($item['project']))
                        @php($project = $item['project'])
                        <a href="{{ $project['href'] }}" class="explorer-project">
                            {{-- TODO: the real (anonymised) screenshot. --}}
                            <span class="explorer-project__frame" aria-hidden="true">
                                @if ($project['visual'] === 'site')
                                    <span class="explorer-project__site"><span class="explorer-project__bar"><i></i><i></i><i></i></span><span class="explorer-project__page"><span></span><i></i><i></i><b></b></span></span>
                                @else
                                    <x-site.project-interface :variant="$project['visual']" />
                                @endif
                            </span>
                            <span class="explorer-project__body">
                                <span class="chapter-chip">{{ $project['type'] }}</span>
                                <span class="explorer-project__title">{{ $project['title'] }}</span>
                                <span class="explorer-project__line">{{ $project['line'] }}</span>
                                @if (! empty($project['metric']))
                                    <span class="explorer-project__metric"><b data-count>{{ $project['metric'][0] }}</b><span>{{ $project['metric'][1] }}</span></span>
                                @endif
                                {{-- The card is the link: this is how it reads, not a second link. --}}
                                <span class="arrow-link btn-light explorer-project__more">
                                    <span class="arrow-link__circle" aria-hidden="true"><span class="arrow-link__dot"></span><span class="arrow-link__fill"></span><svg class="arrow-link__icon" viewBox="0 0 11 10" fill="currentColor"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z" /></svg></span>
                                    <span class="arrow-link__label">Vezi proiectul<span class="arrow-link__border" aria-hidden="true"></span></span>
                                </span>
                            </span>
                        </a>
                    @elseif (! empty($item['modules']))
                        <div class="explorer-modules">
                            <p class="explorer-modules__label">Module tipice:</p>
                            <ul>
                                @foreach ($item['modules'] as $module)
                                    <li>{{ $module }}</li>
                                @endforeach
                            </ul>
                            <x-ui.arrow-link href="/contact" class="explorer-modules__link">Discută un proiect de acest tip</x-ui.arrow-link>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
