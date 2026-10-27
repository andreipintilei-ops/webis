{{--
    What comes with every project, in one large white panel divided into
    cells by hairlines: each a soft icon, a name and a line. One calm block
    rather than a set of cards (css/site/sections.css). On the light
    chapters, under a head.

    `items`: list of [icon (components/section/icon), name, line]; three to
    a row from 1024px, two from 768px. Four of them: two by two, then four
    to a row from 1280px.
--}}
@props(['items'])

<ul {{ $attributes->class(['fpanel', 'fpanel--four' => count($items) === 4]) }} data-reveal>
    @foreach ($items as [$icon, $name, $line])
        <li class="fpanel__cell" data-reveal-child>
            <span class="fpanel__icon"><x-section.icon :name="$icon" /></span>
            <h3 class="fpanel__name">{{ $name }}</h3>
            <p class="fpanel__line">{{ $line }}</p>
        </li>
    @endforeach
</ul>
