<?php

namespace App\Support\Legacy\Import;

use App\Models\Redirect;
use RuntimeException;

/**
 * The owner's reviewed URL map (`url-map-reviewed.csv`): every old URL with
 * its final `decision` and `target_path`. The only input `legacy:import` and
 * `legacy:verify` read — never the generated `url-map.csv`, which a re-run of
 * the inventory would overwrite.
 *
 * Read-only. Tolerates the BOM Excel adds and passes fgetcsv's escape
 * explicitly (PHP 8.4 deprecates the default).
 */
final class ReviewedMap
{
    /** Decisions that become content on the new site. */
    public const array CONTENT_DECISIONS = ['article', 'page', 'legal', 'contact', 'industry', 'project', 'category'];

    /** @param list<array<string, string>> $rows */
    private function __construct(private readonly array $rows) {}

    public static function read(string $path): self
    {
        $handle = is_file($path) ? fopen($path, 'r') : false;

        if ($handle === false) {
            throw new RuntimeException("Cannot read the reviewed URL map at {$path}.");
        }

        $header = fgetcsv($handle, null, ',', '"', '');

        if (! is_array($header) || $header === [null]) {
            fclose($handle);

            throw new RuntimeException("The reviewed URL map at {$path} is empty.");
        }

        $header = array_map(fn (?string $cell): string => trim((string) $cell), $header);
        $header[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        foreach (['old_path', 'decision', 'target_path'] as $required) {
            if (! in_array($required, $header, true)) {
                fclose($handle);

                throw new RuntimeException("The reviewed URL map has no `{$required}` column.");
            }
        }

        $rows = [];

        while (($cells = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if ($cells === [null] || $cells === []) {
                continue;
            }

            $cells = array_pad(array_map(fn (?string $cell): string => (string) $cell, $cells), count($header), '');
            $row = array_combine($header, array_slice($cells, 0, count($header)));
            $row['old_path'] = Redirect::normalisePath($row['old_path'] !== '' ? $row['old_path'] : ($row['old_url'] ?? ''));
            $row['decision'] = trim($row['decision']);
            $row['target_path'] = trim($row['target_path']);
            $rows[] = $row;
        }

        fclose($handle);

        return new self($rows);
    }

    /** @return list<array<string, string>> */
    public function rows(): array
    {
        return $this->rows;
    }

    /** @return list<array<string, string>> */
    public function decided(string ...$decisions): array
    {
        return array_values(array_filter($this->rows, fn (array $row): bool => in_array($row['decision'], $decisions, true)));
    }

    /**
     * Whether the row's target is a different address from its own: then the
     * old address 301s there (an old category at the root, `/12705-2`).
     *
     * @param  array<string, string>  $row
     */
    public static function targetDiffers(array $row): bool
    {
        return $row['target_path'] !== ''
            && Redirect::normalisePath($row['target_path']) !== $row['old_path'];
    }

    /**
     * The last path segment of a row's target: the slug its item gets.
     *
     * @param  array<string, string>  $row
     */
    public static function targetSlug(array $row): string
    {
        $path = Redirect::normalisePath($row['target_path'] !== '' ? $row['target_path'] : $row['old_path']);

        return (string) basename($path);
    }
}
