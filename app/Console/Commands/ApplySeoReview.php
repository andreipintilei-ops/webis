<?php

namespace App\Console\Commands;

use App\Enums\PageType;
use App\Models\Page;
use App\Models\PostCategory;
use App\Models\Project;
use App\Settings\SeoSettings;
use App\Support\Seo\SeoData;
use App\Support\Seo\SeoText;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * Applies a reviewed list of SEO values (a CSV: path, field, value, note —
 * the shape seo:fill-missing writes, its values rewritten by hand): each
 * `title` and `description` into its page's SEO fields, after the same
 * checks as the audit (a title within 60 characters with " | Webis", a
 * description within 155, no "organizați…"). Rows that are not a value
 * (`action`: a page to fix or remove), paths with no page and values that
 * break a rule are listed, not applied.
 */
class ApplySeoReview extends Command
{
    protected $signature = 'seo:apply-reviewed
        {file : The reviewed CSV (path, field, value, note)}
        {--dry-run : Show what would be written, write nothing}';

    protected $description = 'Apply reviewed SEO titles and descriptions from a CSV';

    public function handle(SeoSettings $seo): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error("No such file: {$file}");

            return self::FAILURE;
        }

        $handle = fopen($file, 'r');
        $head = array_map(fn (string $column): string => strtolower(trim($column, "\u{FEFF} ")), fgetcsv($handle, escape: '') ?: []);
        [$applied, $skipped] = [[], []];

        while (($row = fgetcsv($handle, escape: '')) !== false) {
            $row = array_combine($head, array_pad($row, count($head), ''));
            [$path, $field, $value, $note] = [trim($row['path']), trim($row['field']), trim($row['value']), trim($row['note'] ?? '')];

            if (! in_array($field, ['title', 'description'], true)) {
                $skipped[] = [$path, $field, $value.($note !== '' ? " — {$note}" : ''), 'not a value: for a person'];

                continue;
            }

            $item = $this->find($path);

            if ($item === null) {
                $skipped[] = [$path, $field, $value, 'no such page'];

                continue;
            }

            $value = $field === 'title' ? (string) preg_replace('/\s*\|\s*Webis\s*$/u', '', $value) : $value;
            $problem = match (true) {
                $value === '' => 'empty',
                $field === 'title' && mb_strlen($value.$seo->title_suffix) > SeoText::TITLE_MAX => 'title over '.SeoText::TITLE_MAX.' characters with "'.$seo->title_suffix.'"',
                $field === 'description' && mb_strlen($value) > SeoText::DESCRIPTION_MAX => 'description over '.SeoText::DESCRIPTION_MAX.' characters',
                preg_match('/organiza(ț|ţ|t)i/iu', $value) === 1 => '"organizați…"',
                default => null,
            };

            if ($problem !== null) {
                $skipped[] = [$path, $field, $value, $problem];

                continue;
            }

            if (! $this->option('dry-run')) {
                $item->seo = SeoData::fromArray([...$item->seo->toArray(), $field => $value]);
                $item->save();
            }

            $applied[] = [$path, $field, mb_strimwidth($value, 0, 80, '…')];
        }

        fclose($handle);

        $this->info(($this->option('dry-run') ? 'Would apply ' : 'Applied ').count($applied).' value(s).');

        if ($skipped !== []) {
            $this->warn(count($skipped).' row(s) not applied:');
            $this->table(['Path', 'Field', 'Value', 'Why'], array_map(fn (array $row): array => [$row[0], $row[1], mb_strimwidth($row[2], 0, 90, '…'), $row[3]], $skipped));
        }

        return self::SUCCESS;
    }

    /** The page, project or blog category at a public path, or null. */
    private function find(string $path): ?Model
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        return match (true) {
            count($segments) === 2 && $segments[0] === 'proiect' => Project::where('slug', $segments[1])->first(),
            count($segments) === 3 && $segments[0] === 'blog' && $segments[1] === 'categorie' => PostCategory::where('slug', $segments[2])->first(),
            count($segments) === 2 && $segments[0] === 'servicii' => Page::where('type', PageType::Industry)->where('slug', $segments[1])->first(),
            count($segments) === 1 => Page::where('type', '!=', PageType::Industry)->where('slug', $segments[0])->first(),
            default => null,
        };
    }
}
