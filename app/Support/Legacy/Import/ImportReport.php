<?php

namespace App\Support\Legacy\Import;

use Illuminate\Support\Facades\File;

/**
 * What one `legacy:import` run did, step by step: counters, and lists of the
 * rows worth a look (refused, kept, discarded canonicals, missing files …).
 * Written to `import-report-{timestamp}.json` and summarised on the console.
 */
final class ImportReport
{
    /** @var array<string, array<string, int>> step → counter → n */
    private array $counts = [];

    /** @var array<string, array<string, list<mixed>>> step → list name → entries */
    private array $lists = [];

    /** @var array<string, string> step → why it did not run */
    private array $refused = [];

    public function __construct(private readonly array $options = []) {}

    public function count(string $step, string $counter, int $by = 1): void
    {
        $this->counts[$step][$counter] = ($this->counts[$step][$counter] ?? 0) + $by;
    }

    public function add(string $step, string $list, mixed $entry): void
    {
        $this->lists[$step][$list][] = $entry;
    }

    public function refuse(string $step, string $reason): void
    {
        $this->refused[$step] = $reason;
    }

    /** @return array<string, int> */
    public function counts(string $step): array
    {
        return $this->counts[$step] ?? [];
    }

    /** @return list<mixed> */
    public function list(string $step, string $list): array
    {
        return $this->lists[$step][$list] ?? [];
    }

    public function refusal(string $step): ?string
    {
        return $this->refused[$step] ?? null;
    }

    /** @return list<string> */
    public function steps(): array
    {
        return array_values(array_unique([...array_keys($this->counts), ...array_keys($this->lists), ...array_keys($this->refused)]));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $steps = [];

        foreach ($this->steps() as $step) {
            $steps[$step] = array_filter([
                'refused' => $this->refused[$step] ?? null,
                'counts' => $this->counts[$step] ?? [],
                ...($this->lists[$step] ?? []),
            ], fn (mixed $value): bool => $value !== null && $value !== []);
        }

        return ['generated_at' => now()->toIso8601String(), 'options' => $this->options, 'steps' => $steps];
    }

    public function write(string $directory): string
    {
        File::ensureDirectoryExists($directory);
        $path = $directory.'/import-report-'.now()->format('Ymd-His').'.json';
        File::put($path, (string) json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $path;
    }
}
