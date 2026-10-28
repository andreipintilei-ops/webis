<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * The footer's closing call, page by page (App\Support\FooterCta): the
 * default, the five sections' own, and the three pages that had theirs
 * written in their views until now.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $call = fn (string $title, string $intro, string $button, string $url = '/contact/'): array => compact('title', 'intro', 'button', 'url');

        $this->migrator->add('footer_cta.pages', [
            'default' => $call('Ai un proiect în minte?', 'Un site de prezentare, un magazin online sau software la comandă: spune-ne ce vrei să construiești și revenim cu pașii următori.', 'Hai să discutăm'),
            'software' => $call('Ce ai vrea să facă aplicația ta?', 'Spune-ne cum lucrați acum și ce vă încurcă, iar noi revenim cu o propunere pe etape.', 'Discută un proiect'),
            'products' => $call('Vrei să vezi un produs în acțiune?', 'Spune-ne ce produs te interesează și îți facem o prezentare pe un exemplu apropiat de ce faci tu.', 'Cere o prezentare', '/contact/?cerere=prezentare'),
            'web' => $call('Site nou sau refacerea celui vechi?', 'Spune-ne ce ai acum și ce vrei să obții, iar noi îți propunem pașii, fără să pierzi pozițiile din Google.', 'Discută despre site'),
            'portfolio' => $call('Îți place ce ai văzut?', 'Spune-ne ce vrei să construiești și îți arătăm proiecte apropiate de al tău.', 'Hai să discutăm'),
            'blog' => $call('Ai o întrebare care nu e aici?', 'Scrie-ne și îți răspundem. Poate devine chiar următorul articol.', 'Scrie-ne'),
            'shop' => $call('Vrei să vinzi online?', 'Spune-ne ce produse ai și cum livrezi acum, iar noi revenim cu pașii următori și o ofertă pe etape.', 'Cere o ofertă'),
            'presentation' => $call('Vrei un site nou pentru firma ta?', 'Spune-ne ce face firma ta și ce vrei să afle clienții, iar noi revenim cu pașii următori și o ofertă.', 'Cere o ofertă'),
            'project' => $call('Ai un proiect asemănător?', 'Spune-ne ce vrei să construiești și revenim cu o propunere.', 'Hai să discutăm'),
        ]);
    }
};
