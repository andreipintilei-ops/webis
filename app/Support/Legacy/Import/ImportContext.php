<?php

namespace App\Support\Legacy\Import;

use App\Support\TrailingSlash;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Everything one `legacy:import` run shares between its steps: the reviewed
 * map, the options, where files go, the media map and the report.
 */
final class ImportContext
{
    /** The old site's clock: WordPress dates are wall-clock Bucharest time. */
    public const string LEGACY_ZONE = 'Europe/Bucharest';

    private ?MediaMap $media = null;

    /** @var list<array<string, mixed>>|null */
    private ?array $mediaSource = null;

    public function __construct(
        public readonly ReviewedMap $map,
        public readonly LegacySource $legacy,
        public readonly ImportReport $report,
        public readonly string $outputDirectory,
        public readonly string $mediaSourcePath,
        public readonly string $archivePath,
        public readonly bool $dryRun = false,
        public readonly bool $publish = false,
        public readonly bool $force = false,
        public readonly bool $loadRedirects = false,
    ) {}

    public function mediaMapPath(): string
    {
        return $this->outputDirectory.'/media-map.json';
    }

    public function media(): MediaMap
    {
        return $this->media ??= MediaMap::load($this->mediaMapPath());
    }

    /**
     * `media-source.json`: every attachment with its derivatives.
     *
     * @return list<array<string, mixed>>
     */
    public function mediaSource(): array
    {
        if ($this->mediaSource !== null) {
            return $this->mediaSource;
        }

        if (! is_file($this->mediaSourcePath)) {
            throw new RuntimeException("Cannot read the media source at {$this->mediaSourcePath} (written by legacy:inventory).");
        }

        $data = json_decode((string) File::get($this->mediaSourcePath), true);

        return $this->mediaSource = is_array($data) ? array_values(array_filter($data, is_array(...))) : [];
    }

    /**
     * The asset holding an attachment, by its WordPress id.
     */
    public function assetForAttachment(?int $wpId): ?int
    {
        if ($wpId === null) {
            return null;
        }

        foreach ($this->mediaSource() as $attachment) {
            if ((int) ($attachment['wp_id'] ?? 0) === $wpId) {
                return $this->media()->assetId((string) ($attachment['url'] ?? ''));
            }
        }

        return null;
    }

    /**
     * A file write that a dry run skips.
     */
    public function write(string $path, string $contents): void
    {
        if ($this->dryRun) {
            return;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    /**
     * Each old address's final target, for the converters' link rewriting:
     * content keeps its address or moves to its target, redirects go to
     * theirs. Gone and undecided rows are left out.
     *
     * @return array<string, string>
     */
    public function links(): array
    {
        $links = [];

        foreach ($this->map->rows() as $row) {
            if ($row['target_path'] !== '' && $row['decision'] !== 'gone') {
                $links[$row['old_path']] = TrailingSlash::append($row['target_path']);
            }
        }

        return $links;
    }

    /**
     * A WordPress date (`2024-07-12 10:00:09`, wall-clock Bucharest) as a
     * moment; a bare `Y-m-d` takes the given time of day. Null for WordPress's
     * zero dates and anything unreadable.
     */
    public static function legacyDate(?string $value, ?string $timeOfDay = null): ?CarbonImmutable
    {
        $value = trim((string) $value);

        if ($value === '' || str_starts_with($value, '0000')) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            $value .= ' '.($timeOfDay !== null && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $timeOfDay) === 1 ? $timeOfDay : '00:00:00');
        }

        try {
            return CarbonImmutable::parse($value, self::LEGACY_ZONE)->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
