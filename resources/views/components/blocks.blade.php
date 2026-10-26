{{--
    A page's blocks, in order. Every asset the blocks use is loaded in one
    query and handed to each block view as `$assets` (keyed by id).

    Each view gets: `$data` (the block's data), `$block` (id, type…),
    `$assets`, `$isFirst` — the first block holds the page's main image, so
    it is the one to load with priority — and `$onBackdrop`.

    `$onBackdrop`: the page opens on a dark hero, whose background is fixed
    behind the page (blocks/hero.blade.php), and this block comes straight
    after it — or after others like it — and asks to sit on that background
    (`background: "hero"`). Its section is then see-through, white on the
    hero's background. Anywhere else the setting is ignored.

    Types without a template yet (or retired ones) are skipped.

    `featuresLayout`: a page template's choice of layout for the features
    block (home: "services", /clienti: "services-plain"); left null, the
    block's own `layout` setting applies.
--}}
@props(['blocks' => [], 'featuresLayout' => null])

@php
    $registry = app(\App\Blocks\BlockRegistry::class);
    $blocks = is_array($blocks) ? $blocks : [];
    $assets = \App\Models\Asset::query()
        ->with('media')
        ->whereKey($registry->assetIds($blocks))
        ->get()
        ->keyBy('id');

    // Does the page open on a hero whose background stays behind it?
    $first = $blocks[0] ?? null;
    $stage = is_array($first) && ($first['type'] ?? null) === \App\Blocks\HeroBlock::type()
        && \App\Blocks\HeroBlock::isDark((array) ($first['data'] ?? []));
@endphp

@foreach ($blocks as $block)
    @continue (! is_array($block) || ! $registry->has($block['type'] ?? ''))

    @php
        // The chain of blocks on the hero's background breaks at the first
        // block that does not ask for it.
        $onBackdrop = $stage && ! $loop->first && (($block['data']['background'] ?? null) === 'hero');
        $stage = $stage && ($loop->first || $onBackdrop);
    @endphp

    @includeIf($registry->get($block['type'])->view(), [
        'data' => $block['data'] ?? [],
        'block' => $block,
        'assets' => $assets,
        'isFirst' => $loop->first,
        'onBackdrop' => $onBackdrop,
        'featuresLayout' => $featuresLayout,
    ])
@endforeach
