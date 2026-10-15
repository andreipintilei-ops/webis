{{-- Contact pages keep their CMS title and SEO settings, with the site's shared enquiry form. --}}
<x-layouts.public :title="$page->seo->title ?: $page->title" :description="$page->seo->description ?: 'Spune-ne ce vrei să construiești: software la comandă, produse SaaS, site-uri sau magazine online. Webis, Iași · 0770 700 607.'" header-theme="dark" hero-leave="overlap" :footer-cta="false" :footer-on-backdrop="true">
    @push('backdrop')
        @include('blocks.partials.hero-backdrop', [
            'backdrop' => 'gradient',
            'palette' => 'violet',
            'image' => null,
            'isFirst' => true,
            'fixed' => true,
        ])
    @endpush

    {{-- No data-intro: the opening animation is the home page's alone. The
         backdrop still rests full screen behind the whole page. --}}
    <section class="contact-hero has-hero-planes" aria-labelledby="contact-hero-title" data-backdrop-area data-nav-tone="dark">
        @include('partials.hero-planes')

        <div class="contact-hero__inner">
            <p class="contact-hero__label">Contact · Webis</p>
            <h1 id="contact-hero-title" class="contact-hero__title">
                <span class="hero-line"><span class="hero-line__inner">Hai să discutăm</span></span>
                <span class="hero-line"><span class="hero-line__inner">despre proiectul tău.</span></span>
            </h1>
            <div class="contact-hero__foot">
                <p>Software la comandă, produse SaaS, site-uri și magazine online.<br>Găsim împreună soluția potrivită.</p>
            </div>
        </div>
    </section>

    <x-site.contact :page="true" />
</x-layouts.public>
