{{-- A CMS page at the site root, built from its blocks. --}}
<x-layouts.public
    :title="$page->seo->title ?: $page->title"
    :header-theme="\App\Blocks\HeroBlock::headerThemeFor($page->blocks)"
>
    <x-blocks :blocks="$page->blocks ?? []" />
</x-layouts.public>
