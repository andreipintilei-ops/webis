{{--
    /servicii — what Webis does, and the industry pages under it at the old
    site's addresses (/servicii/{slug}/, PageController@industry):
    - the internal pages' hero, the three services across its foot (as /web
      closes its hero on its two offers);
    - the industries: a centred head, then an index of them on hairlines,
      each to its page (css/site/services-index.css);
    then the footer's closing call.
--}}
@php
    use App\Enums\PageType;
    use App\Models\Page;
    use App\Support\PublicUrls;
    use App\Support\SiteNavigation;
    use Illuminate\Support\Str;

    $times = [
        '/software-la-comanda/' => 'Sisteme construite de la zero',
        '/produse/' => 'Gata de folosit, lunar',
        '/web/' => 'Site-uri și magazine',
    ];

    // The industry pages by name, without the "Creare site" each title
    // opens with: the index reads as the list of domains it is. The one about
    // a platform rather than a domain stays out of it (its page stays up).
    $industries = Page::query()
        ->published()
        ->where('type', PageType::Industry)
        ->where('slug', '!=', 'creare-site-wordpress')
        ->get(['id', 'type', 'slug', 'title'])
        ->map(fn (Page $page): array => [
            'name' => Str::ucfirst(trim((string) preg_replace('/^(creare|realizare)\s+site\s+/iu', '', $page->title))),
            'href' => PublicUrls::for($page),
        ])
        ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
        ->values();

    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<x-layouts.public
    title="Servicii"
    description="Software la comandă, produse SaaS, site-uri și magazine online, pentru companii, instituții și universități."
    header-theme="dark"
>
    <x-site.page-hero
        label="Servicii · Webis"
        :title="['Ce construim', 'pentru tine.']"
        intro="Software la comandă, produse gata de folosit, site-uri și magazine online, de la aceeași echipă."
        class="page-hero--led"
        :flush="true"
        data-reveal-pace="fast"
    >
        <x-slot:after>
            <ul class="web-hero-offers web-hero-offers--three">
                @foreach (SiteNavigation::services() as $service)
                    <li>
                        <a href="{{ $service['href'] }}" class="web-hero-offer">
                            <span class="web-hero-offer__meta">{{ $times[$service['href']] ?? '' }}</span>
                            <span class="web-hero-offer__title">{{ $service['label'] }}</span>
                            <span class="web-hero-offer__side">
                                <span class="web-hero-offer__text">{{ $service['blurb'] }}</span>
                                <span class="blog-lead__arrow" aria-hidden="true">{!! $arrow !!}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-slot:after>
    </x-site.page-hero>

    @if ($industries->isNotEmpty())
        <section class="chapter chapter--light" aria-labelledby="services-industries-title" data-reveal-pace="fast">
            <div class="chapter__inner">
                <x-section.center-head
                    id="services-industries-title"
                    label="Pe domenii"
                    title="Site-uri gândite pentru felul în care lucrează domeniul tău."
                    highlight="domeniul tău"
                />

                <ul class="services-index" data-reveal>
                    @foreach ($industries as $industry)
                        <li data-reveal-child>
                            <a href="{{ $industry['href'] }}" class="services-index__link">
                                <span>{{ $industry['name'] }}</span>
                                <span class="services-index__arrow" aria-hidden="true">{!! $arrow !!}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</x-layouts.public>
