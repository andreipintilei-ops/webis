{{--
    A block's section heading, as the reusable sections' centred head
    (components/section/center-head, css/site/sections.css): the title in
    the text face (the display face is the hero's alone), an intro under
    it when there is one, and over it, optionally, a label in the violet
    chip. The same on every block, so the pages read in one rhythm.

    `title`: the heading's text. `id`: the heading's id, for the section's
    aria-labelledby. `intro`, `label`: optional. `tone`: "light" (on the
    lavender) or "dark" (on the indigo); the colours follow the chapter's.
--}}
@props(['title', 'id', 'intro' => null, 'label' => null, 'tone' => 'light'])

<header {{ $attributes->class(['chead section-head', 'section-head--dark' => $tone === 'dark']) }} data-reveal="head">
    @if ($label)
        <p class="chead__eyebrow"><span class="title-pill chead__pill" data-title-pill><span class="title-pill__inner">{{ $label }}</span></span></p>
    @endif
    <h2 id="{{ $id }}" class="chead__title">{{ $title }}</h2>
    @if ($intro)
        <p class="chead__intro">{{ $intro }}</p>
    @endif
</header>
