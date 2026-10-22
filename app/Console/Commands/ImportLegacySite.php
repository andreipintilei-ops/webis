<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Support\Legacy\Import\ImportContext;
use App\Support\Legacy\Import\ImportReport;
use App\Support\Legacy\Import\LegacySource;
use App\Support\Legacy\Import\ReviewedMap;
use App\Support\Legacy\Import\Steps\CategoriesStep;
use App\Support\Legacy\Import\Steps\ContentStep;
use App\Support\Legacy\Import\Steps\MediaStep;
use App\Support\Legacy\Import\Steps\ProjectsStep;
use App\Support\Legacy\Import\Steps\RedirectsStep;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Imports the old WordPress site, as decided in the reviewed URL map
 * (`url-map-reviewed.csv`), in this order:
 *
 *   categories → media → articles, pages, industries → projects → redirects
 *
 * Categories first (articles link to them); media second (everything after
 * resolves images through `media-map.json`); redirects last (they point at
 * what was imported). Idempotent, keyed on slug: a re-run refreshes titles,
 * excerpts, SEO and dates and never replaces non-empty content without
 * --force. Imported content is a draft unless --publish.
 *
 * --dry-run runs everything inside a transaction that is rolled back, and
 * writes no files (no uploads, no map, no CSV) — only the report.
 *
 * Reads the legacy database and the uploads zip; never writes to either.
 */
class ImportLegacySite extends Command
{
    protected $signature = 'legacy:import
        {--map= : The reviewed URL map (default: storage/app/private/legacy/url-map-reviewed.csv)}
        {--only=* : Run only these steps: categories, media, articles, pages, industries, projects, redirects}
        {--dry-run : Run inside a rolled-back transaction and write no files}
        {--publish : Publish what is imported (default: drafts)}
        {--force : Replace non-empty content (blocks, covers, galleries) too}
        {--load-redirects : Load the written redirects CSV into the redirects table, as the admin import does}
        {--media-source= : media-source.json (default: next to the map)}
        {--archive= : The old public_html backup zip (default: webis-export/webis-bk.zip)}
        {--output= : Where the media map, redirects CSV, legacy ids and report go (default: storage/app/private/legacy)}';

    protected $description = 'Import the old WordPress site from the reviewed URL map';

    public const array STEPS = ['categories', 'media', 'articles', 'pages', 'industries', 'projects', 'redirects'];

    public function handle(): int
    {
        $steps = $this->steps();

        if ($steps === null) {
            return self::INVALID;
        }

        $output = rtrim((string) ($this->option('output') ?: storage_path('app/private/legacy')), '/\\');
        $mapPath = (string) ($this->option('map') ?: $output.'/url-map-reviewed.csv');

        try {
            $map = ReviewedMap::read($mapPath);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $report = new ImportReport([
            'map' => $mapPath,
            'steps' => $steps,
            'dry_run' => (bool) $this->option('dry-run'),
            'publish' => (bool) $this->option('publish'),
            'force' => (bool) $this->option('force'),
            'load_redirects' => (bool) $this->option('load-redirects'),
        ]);

        $context = new ImportContext(
            map: $map,
            legacy: LegacySource::connect(),
            report: $report,
            outputDirectory: $output,
            mediaSourcePath: (string) ($this->option('media-source') ?: dirname($mapPath).'/media-source.json'),
            archivePath: (string) ($this->option('archive') ?: base_path('webis-export/webis-bk.zip')),
            dryRun: (bool) $this->option('dry-run'),
            publish: (bool) $this->option('publish'),
            force: (bool) $this->option('force'),
            loadRedirects: (bool) $this->option('load-redirects'),
        );

        if ($context->dryRun) {
            $this->warn('Dry run: everything is rolled back and no files are written.');
            DB::beginTransaction();
        }

        $ok = true;

        try {
            $this->reportKept($context);

            foreach ($steps as $step) {
                $this->line("<info>Step: {$step}</info>");
                $ok = $this->runStep($context, $step) && $ok;
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $report->refuse('run', $e->getMessage());
            $ok = false;
        } finally {
            if ($context->dryRun) {
                DB::rollBack();
            }
        }

        $path = $report->write($output);
        $this->summarise($report);
        $this->line("Report: {$path}");

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function runStep(ImportContext $context, string $step): bool
    {
        match ($step) {
            'categories' => app(CategoriesStep::class)->run($context),
            'media' => app(MediaStep::class)->run($context),
            'projects' => app(ProjectsStep::class)->run($context),
            'redirects' => app(RedirectsStep::class)->run($context),
            default => null,
        };

        if (isset(ContentStep::KINDS[$step]) && ! app(ContentStep::class)->run($context, $step)) {
            $this->error($context->report->refusal($step) ?? "The {$step} step refused to run.");

            return false;
        }

        return true;
    }

    /**
     * The map's `skip` rows whose slug the new site already holds (the live
     * pages): reported as kept, never touched.
     */
    private function reportKept(ImportContext $context): void
    {
        foreach ($context->map->decided('skip') as $row) {
            $slug = ReviewedMap::targetSlug($row);
            $page = $slug !== '' ? Page::withTrashed()->where('slug', $slug)->first() : null;

            $context->report->count('kept', $page !== null ? 'skip_rows_existing' : 'skip_rows_not_pages');
            $context->report->add('kept', 'skip_rows', [
                'path' => $row['old_path'],
                'target' => $row['target_path'],
                'status' => $page !== null ? 'exists, kept' : 'not a page here (route or home)',
            ]);
        }

        foreach (ContentStep::PROTECTED_SLUGS as $slug) {
            if (($page = Page::withTrashed()->where('slug', $slug)->first()) !== null) {
                $context->report->add('kept', 'protected_pages', ['slug' => $slug, 'status' => 'exists, kept', 'deleted' => $page->trashed()]);
            }
        }
    }

    /** @return list<string>|null null when an unknown step was asked for */
    private function steps(): ?array
    {
        $only = array_values(array_filter(array_map(trim(...), explode(',', implode(',', (array) $this->option('only'))))));

        if ($only === []) {
            return self::STEPS;
        }

        $unknown = array_diff($only, self::STEPS);

        if ($unknown !== []) {
            $this->error('Unknown step(s): '.implode(', ', $unknown).'. Steps: '.implode(', ', self::STEPS).'.');

            return null;
        }

        // Always in the import's own order, whatever order they were asked in.
        return array_values(array_intersect(self::STEPS, $only));
    }

    private function summarise(ImportReport $report): void
    {
        foreach ($report->steps() as $step) {
            if (($refusal = $report->refusal($step)) !== null) {
                $this->line("<comment>{$step}</comment>: refused — {$refusal}");

                continue;
            }

            $counts = $report->counts($step);

            if ($counts !== []) {
                ksort($counts);
                $this->line("<comment>{$step}</comment>: ".implode(', ', array_map(fn (string $k, int $v): string => "{$k} {$v}", array_keys($counts), $counts)));
            }
        }
    }
}
