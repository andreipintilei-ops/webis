<?php

namespace App\Console\Commands;

use App\Support\Legacy\Import\ReviewedMap;
use App\Support\Legacy\Import\UrlExpectation;
use Dom\HTMLDocument;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * The migration's acceptance gate: requests every old URL in the reviewed
 * map — in the form Google has it, with its trailing slash — against a base
 * URL, and checks each against the outcome its decision calls for
 * (UrlExpectation): 410, a single 301 to a target that answers 200, or 200.
 *
 * A 200 is not enough. Each one must also carry a canonical naming itself
 * (its own path, on the base's host or on the canonical site's —
 * config site.canonical_url — since a local or staging copy still names the
 * real pages), `noindex` only where the old site had it, and a body of at
 * least --min-words words — the tripwire for content an extractor lost.
 *
 * A site that isn't production noindexes every page (the public layout).
 * Against one, --not-production reports that blanket tag as a warning, not
 * a failure on every page; production must never need the flag.
 *
 * Writes every failure and warning to `verify-failures-{timestamp}.csv`.
 * Run it against a staging server on the production stack, not only Herd.
 */
class VerifyLegacySite extends Command
{
    protected $signature = 'legacy:verify
        {--base= : The site to check (default: APP_URL)}
        {--map= : The reviewed URL map (default: storage/app/private/legacy/url-map-reviewed.csv)}
        {--min-words=80 : Fewer words than this in the main content fails a 200}
        {--insecure : Do not verify TLS certificates (a local or staging host)}
        {--not-production : The site is not production: its blanket noindex is a warning, not a failure}
        {--output= : Where the failures CSV goes (default: storage/app/private/legacy)}';

    protected $description = 'Check every old URL answers as the reviewed map says it should';

    /** The robots tag the public layout puts on every page outside production. */
    private const string NOT_PRODUCTION_ROBOTS = 'noindex, nofollow';

    /** @var list<array{path: string, decision: string, expected: string, got: string, severity: string, problem: string}> */
    private array $problems = [];

    public function handle(): int
    {
        // The same instance answers every call in a process (Artisan::call).
        $this->problems = [];
        $base = rtrim((string) ($this->option('base') ?: config('app.url')), '/');
        $output = rtrim((string) ($this->option('output') ?: storage_path('app/private/legacy')), '/\\');
        $mapPath = (string) ($this->option('map') ?: $output.'/url-map-reviewed.csv');
        $minWords = max(0, (int) $this->option('min-words'));

        try {
            $map = ReviewedMap::read($mapPath);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $checked = 0;
        $passed = 0;

        foreach ($map->rows() as $row) {
            if ($row['decision'] === '') {
                continue; // undecided: nothing to expect
            }

            $checked++;
            $before = $this->failureCount();
            $this->check($row, $base, $minWords);
            $passed += (int) ($this->failureCount() === $before);
        }

        $warnings = count($this->problems) - $this->failureCount();
        $file = $this->writeProblems($output);

        $this->line("Checked {$checked} URLs against {$base}: {$passed} passed, {$this->failureCount()} failures, {$warnings} warnings.");

        foreach ($this->tally() as $problem => $n) {
            $this->line("  {$n} × {$problem}");
        }

        if ($file !== null) {
            $this->line("Details: {$file}");
        }

        return $this->failureCount() === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @param array<string, string> $row */
    private function check(array $row, string $base, int $minWords): void
    {
        $expected = UrlExpectation::for($row);
        $path = UrlExpectation::requestPath($row);
        $label = $expected->status === 301 ? "301 → {$expected->target}" : (string) $expected->status;
        $fail = fn (string $got, string $problem, string $severity = 'failure') => $this->problems[] = [
            'path' => $path, 'decision' => $row['decision'], 'expected' => $label,
            'got' => $got, 'severity' => $severity, 'problem' => $problem,
        ];

        $response = $this->fetch($base.$path);

        if (! $response instanceof Response) {
            $fail('error', 'request failed: '.$response);

            return;
        }

        $status = $response->status();

        if ($expected->status === 410) {
            if ($status !== 410) {
                $fail((string) $status, "answers {$status}, not 410");
            }

            return;
        }

        if ($expected->status === 301) {
            $location = (string) $response->header('Location');

            if ($status !== 301 || $location === '') {
                $fail((string) $status, "answers {$status}, not a 301");

                return;
            }

            if (! $expected->locationMatches($location, $base)) {
                $fail("301 → {$location}", 'redirects somewhere else');

                return;
            }

            $absolute = str_starts_with($location, '/') ? $base.$location : $location;
            $final = $this->fetch($absolute);
            $finalStatus = $final instanceof Response ? (string) $final->status() : 'error';

            if ($finalStatus !== '200') {
                $fail("301 → {$location} → {$finalStatus}", $finalStatus[0] === '3' ? 'redirect chain' : "target answers {$finalStatus}");
            }

            return;
        }

        if ($status !== 200) {
            $fail((string) $status, "answers {$status}, not 200");

            return;
        }

        $this->checkPage($row, $path, $base, $response->body(), $minWords, $fail);
    }

    /**
     * The three checks a 200 needs: canonical self-link, noindex as before,
     * enough words.
     *
     * @param  array<string, string>  $row
     * @param  callable(string, string, string=): mixed  $fail
     */
    private function checkPage(array $row, string $path, string $base, string $html, int $minWords, callable $fail): void
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        $canonical = $document->querySelector('link[rel~="canonical"]')?->getAttribute('href');

        if ($canonical === null || trim($canonical) === '') {
            $fail('200', 'no canonical');
        } elseif (! $this->isSelf($canonical, $base.$path, $base)) {
            $fail("canonical {$canonical}", 'canonical is not the page itself');
        }

        $robots = strtolower((string) $document->querySelector('meta[name="robots"]')?->getAttribute('content'));
        $noindex = str_contains($robots, 'noindex');
        $wanted = in_array(trim($row['noindex'] ?? ''), ['1', 'true'], true);

        if ($noindex && ! $wanted && $this->option('not-production') && $robots === self::NOT_PRODUCTION_ROBOTS) {
            $fail("robots \"{$robots}\"", 'noindex everywhere: not production', 'warning');
        } elseif ($noindex !== $wanted) {
            $fail("robots \"{$robots}\"", $noindex ? 'noindex, but the old page was indexed' : 'indexable, but the old page was noindex');
        }

        $words = $this->wordCount($document);

        if ($words < $minWords) {
            $fail("{$words} words", "fewer than {$minWords} words");
        }
    }

    /**
     * Whether a canonical names the page itself: the page's host (a relative
     * canonical counts as the base's) or the canonical site's, and the same
     * path, trailing slash included.
     */
    private function isSelf(string $canonical, string $page, string $base): bool
    {
        $host = fn (string $url): string => strtolower((string) (parse_url($url, PHP_URL_HOST) ?: parse_url($base, PHP_URL_HOST)));
        $hosts = [$host($page), strtolower((string) parse_url((string) config('site.canonical_url'), PHP_URL_HOST))];

        return in_array($host($canonical), $hosts, true)
            && (string) parse_url($canonical, PHP_URL_PATH) === (string) parse_url($page, PHP_URL_PATH);
    }

    /** Words of visible text in <main>, else in <body>. */
    private function wordCount(HTMLDocument $document): int
    {
        $root = $document->querySelector('main') ?? $document->body;

        if ($root === null) {
            return 0;
        }

        foreach ($root->querySelectorAll('script, style, noscript, template') as $node) {
            $node->remove();
        }

        return (int) preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}\'’-]*/u', (string) $root->textContent);
    }

    private function fetch(string $url): Response|string
    {
        try {
            return $this->client()->get($url);
        } catch (ConnectionException $e) {
            return $e->getMessage();
        }
    }

    private function client(): PendingRequest
    {
        return Http::withoutRedirecting()
            ->withOptions(['verify' => ! $this->option('insecure')])
            ->withHeaders(['User-Agent' => 'webis legacy:verify'])
            ->timeout(30);
    }

    private function failureCount(): int
    {
        return count(array_filter($this->problems, fn (array $p): bool => $p['severity'] === 'failure'));
    }

    /** @return array<string, int> */
    private function tally(): array
    {
        $tally = [];

        foreach ($this->problems as $problem) {
            $key = ($problem['severity'] === 'warning' ? 'warning: ' : '').$problem['problem'];
            $tally[$key] = ($tally[$key] ?? 0) + 1;
        }

        arsort($tally);

        return $tally;
    }

    private function writeProblems(string $directory): ?string
    {
        if ($this->problems === []) {
            return null;
        }

        File::ensureDirectoryExists($directory);
        $path = $directory.'/verify-failures-'.now()->format('Ymd-His').'.csv';
        $out = fopen($path, 'w');

        if ($out === false) {
            return null;
        }

        fputcsv($out, ['path', 'decision', 'expected', 'got', 'severity', 'problem'], ',', '"', '');

        foreach ($this->problems as $problem) {
            fputcsv($out, array_values($problem), ',', '"', '');
        }

        fclose($out);

        return $path;
    }
}
