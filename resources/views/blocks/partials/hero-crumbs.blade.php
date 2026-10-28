{{--
    A hero's breadcrumbs, above its title (an industry page): the same trail
    as the page's BreadcrumbList (Seo\StructuredData::breadcrumbs), each
    step a link but the page itself, in the hero's micro labels.

    `crumbs`: [name, path] each, the page last; `dark`: on the dark hero.
--}}
<nav aria-label="Breadcrumb" class="mb-6 md:mb-8">
    <ol @class(['flex flex-wrap items-center gap-x-2 gap-y-1 font-micro text-xs tracking-[0.08em] uppercase', 'text-white/75' => $dark, 'text-neutral-500' => ! $dark])>
        @foreach ($crumbs as [$name, $path])
            <li class="flex items-center gap-2">
                @if ($loop->last)
                    <span aria-current="page">{{ $name }}</span>
                @else
                    <a href="{{ $path }}" @class(['underline-offset-4 transition-colors hover:underline', 'hover:text-white' => $dark, 'hover:text-black' => ! $dark])>{{ $name }}</a>
                    <span aria-hidden="true">›</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
