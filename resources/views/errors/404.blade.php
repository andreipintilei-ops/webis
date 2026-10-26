{{-- Not found: the internal pages' hero (components/site/page-hero), with the
     way back home and to the articles. One gradient from top to bottom: the
     footer shows through to the hero's. --}}
<x-layouts.public title="Pagina nu a fost găsită" :canonical="false" header-theme="dark" :footer-cta="false" :footer-on-backdrop="true">
    <x-site.page-hero
        label="Eroare 404"
        title="Pagina nu mai e aici."
        intro="Poate a fost mutată sau adresa are o greșeală."
        class="page-hero--error"
        :parallax="false"
        :planes="false"
    >
        <div class="page-hero__links">
            <x-ui.pill href="/" tone="dark">Înapoi la prima pagină</x-ui.pill>
            <x-ui.arrow-link :href="route('posts.index')" tone="dark">Vezi articolele</x-ui.arrow-link>
        </div>
    </x-site.page-hero>
</x-layouts.public>
