{{--
    /blog — the listing: the internal pages' hero (components/site/page-hero),
    then on the first page one featured article across the hero's foot
    (css/site/page-hero.css), and the articles as the home page's text cards
    (components/site/post-card), nine to a page (components/site/pagination,
    ?pagina=2). Cards only; the articles' own pages come later (the links
    stay on /blog's slugs). css/site/blog-posts.css.

    The articles are those of the live webis.ro, in its order (DUMMY DATA:
    its dates are all the import's, so these are spread). The titles as there,
    with diacritics and sentence case (the slugs unchanged); the categories
    in Romanian — Design, Dezvoltare, Software, E-commerce… — its generic
    "Blog" and "Info" only when it has no other.
--}}
@php
    // slug, title, category — the featured one is "magazin-online", below.
    $articles = [
        ['cms-wordpress', 'Ce este CMS WordPress și de ce să alegi WordPress', 'WordPress'],
        ['creare-site-iasi-realizare-design-iasi', 'Creare site Iași și realizare design-uri personalizate', 'Design'],
        ['calendar-drag-drop-php-jquery-cu-filtre', 'Calendar drag & drop PHP, jQuery cu filtre', 'Dezvoltare'],
        ['procedura-de-realizare-unui-web-design-iasi-de-calitate', 'Web design Iași de calitate', 'Design'],
        ['php-combinari-de-n-luate-cate-k', 'PHP – Combinări de N elemente luate câte K', 'Dezvoltare'],
        ['explicatie-tag-meta-robots-noindex-nofollow-noarchive-noodp-noydir', 'Ce este tag-ul meta robots', 'Dezvoltare'],
        ['ce-este-algoritmul-md5', 'Ce este algoritmul MD5?', 'Dezvoltare'],
        ['totul-despre-specificatiile-w3c-world-wide-web-consortium-si-recomandarile-wai-web-accessibility-initiative', 'Totul despre specificațiile W3C (World Wide Web Consortium) și recomandările WAI (Web Accessibility Initiative)', 'Dezvoltare'],
        ['de-ce-trebuie-sa-folosim-meta-tag-urile', 'De ce trebuie să folosim meta tag-urile?', 'Dezvoltare'],
        ['ce-este-o-baza-de-date-mysql', 'Ce este o bază de date MySQL?', 'Dezvoltare'],
        ['de-ce-este-logo-ul-important-in-afacerea-ta', 'De ce este logo-ul important în afacerea ta?', 'Design'],
        ['cum-poti-avea-un-magazin-online-de-succes', 'Cum poți avea un magazin online de succes', 'E-commerce'],
        ['importanta-principiului-kiss-keep-it-simple-stupid-in-realizarea-unui-site-web', 'Importanța principiului KISS (Keep It Simple Stupid) în realizarea unui site web', 'Design'],
        ['ce-sunt-cadrele-frame-iframe-de-ce-sa-nu-le-folosim', 'Ce sunt cadrele frame/iframe? De ce să nu le folosim?', 'Dezvoltare'],
        ['beneficiile-unui-site-de-prezentare-pentru-afacerea-ta', 'Beneficiile unui site de prezentare pentru afacerea ta', 'Dezvoltare'],
        ['ce-este-google-ads-facebook-ads-si-avantajele-utilizarii-google-ads-facebook-ads', 'Ce este Google Ads & Facebook Ads și avantajele utilizării Google Ads & Facebook Ads', 'Marketing'],
        ['ce-este-mentenanta-si-beneficiile-mentenantei-pentru-un-site-web', 'Ce este mentenanța și beneficiile mentenanței pentru un site web', 'Mentenanță'],
        ['ce-este-gazduirea-wordpress-si-cum-alegi-serviciile-potrivite-de-gazduire-pentru-un-site-wordpress', 'Ce este găzduirea WordPress și cum alegi serviciile potrivite de găzduire pentru un site WordPress', 'Găzduire'],
        ['ce-este-un-site-custom-si-avantajele-utilizarii-unui-site-custom', 'Ce este un site custom și avantajele utilizării unui site custom', 'Dezvoltare'],
        ['ce-este-seo-off-page-si-factorii-care-influenteaza-seo-off-page', 'Ce este SEO Off-Page și factorii care influențează SEO Off-Page', 'SEO'],
        ['ce-este-elementor', 'Ce este Elementor și diferența dintre varianta gratuită și varianta Pro', 'Design'],
        ['ce-este-seo-on-page', 'Ce este SEO & SEO On-Page și importanța acestora', 'SEO'],
        ['ce-este-un-browser-web', 'Ce este un browser web și cele mai utilizate browsere', 'Info'],
        ['ce-este-un-logo', 'Ce este un logo și diferența dintre un vector logo & raster logo', 'Design'],
        ['ce-este-frontend-backend', 'Ce este FrontEnd & BackEnd și tehnologiile & limbajele de programare utilizate', 'Dezvoltare'],
        ['imbunatatiti-vizibilitatea-afacerii-dvs-fotografice-prin-crearea-unui-site-web-puternic', 'Îmbunătățiți vizibilitatea afacerii dvs. fotografice prin crearea unui site web puternic', 'Info'],
        ['cum-sa-iti-transformi-afacerea-de-pensiune-sau-hotel-intr-un-succes-online-cu-ajutorul-unui-site-web-puternic', 'Cum să îți transformi afacerea de pensiune sau hotel într-un succes online cu ajutorul unui site web puternic', 'Blog'],
        ['creare-site-uri-web-personalizate-pentru-agentii-de-turism-ghidul-complet-pentru-o-prezenta-online-de-succes', 'Creare site-uri web personalizate pentru agenții de turism: ghidul complet pentru o prezență online de succes', 'Info'],
        ['site-ul-web-al-primariei-tale-cum-sa-il-optimizezi-pentru-a-imbunatati-comunicarea-cu-cetatenii', 'Site-ul web al primăriei tale: cum să îl optimizezi pentru a îmbunătăți comunicarea cu cetățenii', 'Info'],
        ['cum-sa-ai-un-site-web-performant-pentru-cabinetul-tau-de-avocatura', 'Cum să ai un site web performant pentru cabinetul tău de avocatură', 'Info'],
        ['ce-reprezinta-enterprise-resource-planning-si-cum-va-influenteaza-afacerea', 'Ce reprezintă Enterprise Resource Planning și cum vă influențează afacerea?', 'Software'],
        ['ce-reprezinta-cache-ul-si-cum-va-poate-imbunatati-perfomanta-aplicatiei', 'Ce reprezintă cache-ul și cum vă poate îmbunătăți performanța aplicației?', 'Info'],
        ['ce-tip-de-gazduire-poti-alege-in-functie-de-nevoile-site-ului-tau', 'Ce tip de găzduire poți alege în funcție de nevoile site-ului tău?', 'Găzduire'],
        ['ce-reprezinta-domeniu-hosting-si-de-ce-sunt-esentiale-pentru-realizarea-unui-magazin-online-sau-site-de-prezentare', 'Ce reprezintă domeniu & hosting și de ce sunt esențiale pentru realizarea unui magazin online sau site de prezentare?', 'Dezvoltare'],
        ['ce-reprezinta-limbajele-de-programare-client-side-server-side-si-care-sunt-diferentele-dintre-acestea', 'Ce reprezintă limbajele de programare client-side & server-side și care sunt diferențele dintre acestea?', 'Dezvoltare'],
        ['ce-reprezinta-xampp', 'Ce reprezintă XAMPP?', 'Dezvoltare'],
        ['ce-reprezinta-cronjob', 'Ce reprezintă cronjob?', 'Dezvoltare'],
        ['ce-este-hotjar-si-cum-va-ajuta-sa-imbunatatiti-experienta-utilizatorilor', 'Ce este Hotjar și cum vă ajută să îmbunătățiți experiența utilizatorilor?', 'Info'],
        ['care-este-diferenta-dintre-api-si-web-service', 'Care este diferența dintre API și web service?', 'Dezvoltare'],
        ['de-ce-sa-conectezi-un-site-la-google-analytics', 'De ce să conectezi un site la Google Analytics?', 'Dezvoltare'],
        ['vizualizarea-de-pagini-cu-elementor-avantaje-si-dezavantaje', 'Vizualizarea de pagini cu Elementor – avantaje și dezavantaje!', 'Dezvoltare'],
        ['ce-este-css-si-cum-ne-ajuta-sa-editam-un-website', 'Ce este CSS și cum ne ajută să edităm un website?', 'Dezvoltare'],
        ['ce-este-rezolutia-unui-device-si-cum-influenteaza-design-ul-responsive', 'Ce este rezoluția unui device și cum influențează design-ul responsive?', 'SEO'],
        ['ce-este-designul-responsive-si-cum-influenteaza-site-ul-tau', 'Ce este designul responsive și cum influențează site-ul tău', 'SEO'],
        ['cum-ne-ajuta-google-analytics-sa-avem-o-afacere-de-succes', 'Cum ne ajută Google Analytics să avem o afacere de succes?', 'Marketing'],
        ['ce-este-certificatul-ssl-de-ce-sa-il-obtinem', 'Ce este certificatul SSL? De ce să îl obținem?', 'Dezvoltare'],
    ];

    // DUMMY DATA: the dates, spread over the months, newest first.
    $posts = collect($articles)->map(fn (array $article, int $i) => [
        'slug' => $article[0],
        'title' => $article[1],
        'category' => $article[2],
        'date' => \Carbon\CarbonImmutable::parse('2026-10-01')->subDays(12 * $i),
        // TODO: route('posts.show', …) once the article page exists.
        'href' => url('/blog/'.$article[0]),
    ]);

    $featured = $posts->firstWhere('slug', 'cum-poti-avea-un-magazin-online-de-succes');
    $featured['excerpt'] = 'Ce este un magazin online, ce avantaje are și ce contează ca să aibă succes — de la factorii-cheie până la felul în care îi crești vizibilitatea.';
    $rest = $posts->reject(fn (array $post) => $post['slug'] === $featured['slug'])->values();

    // The categories, for the sidebar: each with its address (?categorie=seo)
    // and how many articles it has, most first. An unknown one shows them all.
    $categories = $posts->countBy('category')->sortDesc()
        ->map(fn (int $count, string $name) => [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'count' => $count,
        ])->values();
    $category = $categories->firstWhere('slug', request()->query('categorie'));

    // Filtered, the featured article is among the rest, not above them.
    if ($category) {
        $rest = $posts->where('category', $category['name'])->values();
    }

    // Nine to a page (?pagina=2); the featured one on the first only. A page
    // past the last is not found.
    $perPage = 9;
    $page = max(1, request()->integer('pagina', 1));
    $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
        $rest->forPage($page, $perPage)->values(),
        $rest->count(),
        $perPage,
        $page,
        ['path' => route('posts.index'), 'pageName' => 'pagina'],
    );
    $paginator->appends(array_filter(['categorie' => $category['slug'] ?? null]));

    abort_if($page > $paginator->lastPage(), 404);

    // The featured article: on the first, unfiltered page only, across
    // the hero's foot (css/site/page-hero.css).
    $showFeatured = $paginator->onFirstPage() && ! $category;

    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<x-layouts.public :title="$page > 1 ? 'Blog, pagina '.$page : 'Blog'" description="Articole despre software la comandă, produse SaaS, site-uri și magazine online, scrise de echipa Webis din Iași." header-theme="dark">
    <x-site.page-hero
        label="Blog · Webis"
        :title="['Articole despre', 'software și web.']"
        intro="Ghiduri și noutăți despre software la comandă, site-uri, magazine online și SEO."
        :class="$showFeatured ? 'page-hero--led' : null"
        :flush="true"
    >
        {{-- The featured article closes the hero, on a hairline across its
             foot, as the home page's rating and logos close its own. --}}
        @if ($showFeatured)
            <x-slot:after>
                <a href="{{ $featured['href'] }}" class="blog-lead">
                    <span class="blog-lead__meta">
                        <span>Articol recomandat</span>
                        <span aria-hidden="true">·</span>
                        <span>{{ $featured['category'] }}</span>
                        <span aria-hidden="true">·</span>
                        <time datetime="{{ $featured['date']->toDateString() }}">{{ $featured['date']->locale('ro')->translatedFormat('j F Y') }}</time>
                    </span>
                    <span class="blog-lead__title"><span>{{ $featured['title'] }}</span></span>
                    <span class="blog-lead__side">
                        <span class="blog-lead__excerpt">{{ $featured['excerpt'] }}</span>
                        <span class="blog-lead__arrow" aria-hidden="true">{!! $arrow !!}</span>
                    </span>
                </a>
            </x-slot:after>
        @endif
    </x-site.page-hero>

    <section id="articole" class="site-blog site-blog--page chapter chapter--light" aria-label="Articole">
        <div class="chapter__inner">
            <!-- DUMMY DATA: the live webis.ro's articles, with spread dates. -->
            <div class="site-blog__layout">
                {{-- The categories: a column at the right from 1024px, a row of
                     chips above the articles below it; each its own address. --}}
                <aside class="blog-categories" aria-labelledby="blog-categories-title">
                    <h2 id="blog-categories-title" class="blog-categories__title">Categorii</h2>
                    <ul class="blog-categories__list">
                        <li>
                            <a href="{{ route('posts.index') }}#articole" class="blog-categories__link"@if (! $category) aria-current="page"@endif data-no-transition>
                                <span>Toate articolele</span><span class="blog-categories__count">{{ $posts->count() }}</span>
                            </a>
                        </li>
                        @foreach ($categories as $item)
                            <li>
                                <a href="{{ route('posts.index', ['categorie' => $item['slug']]) }}#articole" class="blog-categories__link"@if ($category && $category['slug'] === $item['slug']) aria-current="page"@endif data-no-transition>
                                    <span>{{ $item['name'] }}</span><span class="blog-categories__count">{{ $item['count'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </aside>

                <div class="site-blog__main">
                    {{-- In rows of three, each coming in as it is reached; all white
                         here (the home page's colour cycle is for its three alone). --}}
                    <div class="site-blog__rows">
                        @foreach ($paginator->getCollection()->chunk(3) as $row)
                            <ul class="site-blog__posts" data-reveal>
                                @foreach ($row as $post)
                                    <x-site.post-card :post="$post" :index="$loop->index" />
                                @endforeach
                            </ul>
                        @endforeach
                    </div>

                    <x-site.pagination :paginator="$paginator" label="Paginile blogului" />
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
