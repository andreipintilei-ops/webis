<?php

namespace App\Actions\Seo;

use App\Enums\RedirectCode;
use App\Enums\RedirectMatchType;
use App\Models\Redirect;
use App\Support\LinkTarget;
use Illuminate\Support\Facades\DB;

/**
 * Loads a redirects CSV — `sursa;destinatie[;cod[;tip]]`, comma also accepted
 * — into the redirects table: existing sources are updated, invalid rows are
 * skipped and reported by line. The admin's import (RedirectController) and
 * `legacy:import --load-redirects` both go through here, so the file the
 * importer writes is read exactly as an editor's upload would be.
 */
class ImportRedirects
{
    /**
     * @return array{saved: int, skipped: list<int>} skipped: 1-based line numbers
     */
    public function __invoke(string $contents): array
    {
        $rows = $this->readCsv($contents);

        $saved = 0;
        $skipped = [];

        DB::transaction(function () use ($rows, &$saved, &$skipped): void {
            foreach ($rows as $line => $row) {
                $source = Redirect::normalisePath($row[0] ?? '');
                $target = trim($row[1] ?? '');
                $code = RedirectCode::tryFrom((int) ($row[2] ?? 301)) ?? RedirectCode::Permanent;
                $type = RedirectMatchType::tryFrom(trim($row[3] ?? '')) ?? RedirectMatchType::Exact;

                if ($source === '/' || ($code->needsTarget() && ! LinkTarget::isAllowed($target))) {
                    $skipped[] = $line;

                    continue;
                }

                Redirect::query()->updateOrCreate(
                    ['source_path' => $source],
                    ['target' => $code->needsTarget() ? $target : null, 'status_code' => $code, 'match_type' => $type],
                );
                $saved++;
            }
        });

        return ['saved' => $saved, 'skipped' => $skipped];
    }

    /**
     * Rows keyed by their 1-based line number, header row dropped when it
     * does not start with a path.
     *
     * @return array<int, list<string>>
     */
    private function readCsv(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];
        $delimiter = substr_count($lines[0] ?? '', ';') >= substr_count($lines[0] ?? '', ',') ? ';' : ',';

        $rows = [];

        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = array_map(fn (?string $cell): string => trim((string) $cell), str_getcsv($line, $delimiter, '"', ''));

            // A header ("sursa", "source"...) or a comment line.
            if ($index === 0 && ! str_starts_with($cells[0], '/') && ! str_starts_with($cells[0], 'http')) {
                continue;
            }

            $rows[$index + 1] = $cells;
        }

        return $rows;
    }
}
