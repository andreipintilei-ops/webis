<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/*
| legacy:verify against a faked site: each old URL's expected outcome comes
| from its reviewed decision, and every 200 must name itself as canonical,
| keep the old noindex and carry enough words.
*/

function verifyPage(?string $canonical, bool $noindex = false, int $words = 120): string
{
    $head = ($canonical !== null ? "<link rel=\"canonical\" href=\"{$canonical}\">" : '')
        .($noindex ? '<meta name="robots" content="noindex, nofollow">' : '<meta name="robots" content="index, follow">');

    return "<!doctype html><html><head><title>T</title>{$head}</head><body><nav>Meniu</nav><main><h1>Titlu</h1><p>"
        .implode(' ', array_fill(0, $words, 'cuvânt'))
        .'</p><script>var x = "nu se numără";</script></main></body></html>';
}

beforeEach(function () {
    $this->output = storage_path('framework/testing/legacy-verify-'.Str::random(8));
    File::ensureDirectoryExists($this->output);

    $rows = [
        // old_path, decision, target_path, noindex
        ['/ghid-seo', 'article', '/ghid-seo/', ''],
        ['/fara-canonical', 'page', '/fara-canonical/', ''],
        ['/pagina-noindex', 'page', '/pagina-noindex/', ''],
        ['/noindex-vechi', 'page', '/noindex-vechi/', '1'],
        ['/pagina-scurta', 'page', '/pagina-scurta/', ''],
        ['/proiect/zyanya', 'project', '/proiect/zyanya/', ''],
        ['/proiect/duo-stone', 'project', '/proiect/duo-stone/', ''],
        ['/uncategorized', 'gone', '', ''],
        ['/tag/redirectionat', 'gone', '', ''],
        ['/gazduire', 'redirect', '/blog/categorie/web-development/', ''],
        ['/web-development', 'category', '/blog/categorie/web-development/', ''],
        ['/lant', 'redirect', '/intermediar/', ''],
        ['/gresit', 'redirect', '/corect/', ''],
        ['/nedecis', '', '', ''],
    ];

    $csv = "\xEF\xBB\xBFold_url,old_path,decision,target_path,noindex\n";

    foreach ($rows as [$path, $decision, $target, $noindex]) {
        $csv .= "https://www.webis.ro{$path}/,{$path},{$decision},{$target},{$noindex}\n";
    }

    File::put("{$this->output}/url-map-reviewed.csv", $csv);

    $base = 'https://staging.test';

    Http::preventStrayRequests();
    Http::fake(function (Request $request) use ($base) {
        return match (substr($request->url(), strlen($base))) {
            '/ghid-seo/' => Http::response(verifyPage("{$base}/ghid-seo/")),
            '/fara-canonical/' => Http::response(verifyPage(null)),
            '/pagina-noindex/' => Http::response(verifyPage('/pagina-noindex/', noindex: true)),
            '/noindex-vechi/' => Http::response(verifyPage('/noindex-vechi/', noindex: true)),
            '/pagina-scurta/' => Http::response(verifyPage('/pagina-scurta/', words: 5)),
            // A canonical naming another page: a Yoast canonical copied over.
            '/proiect/zyanya/' => Http::response(verifyPage('https://www.webis.ro/proiect/alt-proiect/')),
            // The page itself on the canonical site (config site.canonical_url):
            // a staging copy still names the real page.
            '/proiect/duo-stone/' => Http::response(verifyPage('https://www.webis.ro/proiect/duo-stone/')),
            '/uncategorized/' => Http::response('', 410),
            '/tag/redirectionat/' => Http::response('', 301, ['Location' => '/blog/']),
            '/gazduire/', '/web-development/' => Http::response('', 301, ['Location' => "{$base}/blog/categorie/web-development/"]),
            '/blog/categorie/web-development/' => Http::response(verifyPage('/blog/categorie/web-development/')),
            '/lant/' => Http::response('', 301, ['Location' => '/intermediar/']),
            '/intermediar/' => Http::response('', 301, ['Location' => '/final/']),
            '/gresit/' => Http::response('', 301, ['Location' => '/altundeva/']),
            default => Http::response('', 404),
        };
    });

    $this->exit = Artisan::call('legacy:verify', ['--base' => $base, '--output' => $this->output]);
    $this->console = Artisan::output();

    $files = glob("{$this->output}/verify-failures-*.csv") ?: [];
    $lines = array_map(fn (string $line) => str_getcsv($line, ',', '"', ''), array_slice(file((string) ($files[0] ?? ''), FILE_IGNORE_NEW_LINES) ?: [], 1));
    $this->problems = collect($lines)->groupBy(0)->map(fn ($rows) => $rows->map(fn ($row) => "{$row[4]}: {$row[5]}")->all())->all();
});

afterEach(function () {
    File::deleteDirectory($this->output);
});

it('expects a 410, a single 301 to a 200, or a 200, by decision', function () {
    expect($this->problems)->not->toHaveKeys(['/ghid-seo/', '/uncategorized/', '/gazduire/', '/web-development/', '/noindex-vechi/', '/proiect/duo-stone/'])
        ->and($this->problems['/tag/redirectionat/'])->toBe(['failure: answers 301, not 410'])
        ->and($this->problems['/lant/'])->toBe(['failure: redirect chain'])
        ->and($this->problems['/gresit/'])->toBe(['failure: redirects somewhere else']);
});

it('checks the canonical, noindex and word count of every 200', function () {
    expect($this->problems['/fara-canonical/'])->toBe(['failure: no canonical'])
        ->and($this->problems['/pagina-noindex/'])->toBe(['failure: noindex, but the old page was indexed'])
        ->and($this->problems['/pagina-scurta/'])->toBe(['failure: fewer than 80 words'])
        ->and($this->problems['/proiect/zyanya/'])->toBe(['failure: canonical is not the page itself']);
});

it('fails the run and summarises it', function () {
    expect($this->exit)->toBe(1)
        ->and($this->console)->toContain('Checked 13 URLs against https://staging.test: 6 passed, 7 failures, 0 warnings.');
});

it('reports the blanket noindex of a site that is not production as a warning, when told', function () {
    Artisan::call('legacy:verify', ['--base' => 'https://staging.test', '--output' => $this->output, '--not-production' => true]);

    expect(Artisan::output())
        ->toContain('Checked 13 URLs against https://staging.test: 7 passed, 6 failures, 1 warnings.')
        ->toContain('1 × warning: noindex everywhere: not production');
});
