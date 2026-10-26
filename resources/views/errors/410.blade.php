{{-- Gone: an old address deliberately retired (a feed, an attachment page),
     rendered by App\Actions\Seo\HandleNotFound. The 404's hero
     (components/site/page-hero), with the way back home and to the articles. --}}
<x-layouts.public title="Pagina a fost ștearsă" :canonical="false" header-theme="dark" :footer-cta="false" :footer-on-backdrop="true">
    <x-site.page-hero
        label="Eroare 410"
        title="Pagina a fost ștearsă definitiv."
        intro="Adresa aceasta nu mai există pe site. Restul îl găsești mai jos."
        class="page-hero--error"
        :planes="false"
    >
        <div class="page-hero__links">
            <x-ui.pill href="/" tone="dark">Înapoi la prima pagină</x-ui.pill>
            <x-ui.arrow-link :href="route('posts.index')" tone="dark">Vezi articolele</x-ui.arrow-link>
        </div>
    </x-site.page-hero>
</x-layouts.public>
