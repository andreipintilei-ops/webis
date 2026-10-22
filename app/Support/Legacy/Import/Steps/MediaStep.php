<?php

namespace App\Support\Legacy\Import\Steps;

use App\Actions\Media\AttachUpload;
use App\Models\Asset;
use App\Support\Legacy\Import\ImportContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Each WordPress attachment's original, read straight out of the backup zip
 * (never extracted whole) and attached to a new Asset through AttachUpload,
 * so SVGs are sanitised and sizes recorded as for any upload. The original and
 * every resized copy WordPress made of it map to that one asset in
 * `media-map.json`; a re-run reuses the map instead of uploading again.
 *
 * Only what the asset library holds is imported: images (AttachUpload's
 * accepted types). `.zip` attachments and the rest (videos, PDFs, Lottie
 * JSON) are listed, not imported, and their URLs 410 with the other unmapped
 * ones. An image over the media library's size cap comes in as its largest
 * resized copy that fits.
 *
 * Reads only `wp-content/uploads/` entries: the archive also holds the old
 * site's `wp-config.php`, which nothing here opens.
 */
final class MediaStep
{
    public const string NAME = 'media';

    private const string UPLOADS = 'wp-content/uploads/';

    /** @var array<string, string> relative upload path → archive entry name */
    private array $entries = [];

    public function __construct(private readonly AttachUpload $attachUpload) {}

    public function run(ImportContext $context): void
    {
        $report = $context->report;
        $map = $context->media();
        $maxSize = (int) config('media-library.max_file_size');

        $zip = new ZipArchive;

        if ($zip->open($context->archivePath, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException("Cannot open the uploads archive at {$context->archivePath}.");
        }

        $this->indexEntries($zip);
        $jobsBefore = $this->queuedJobs();
        $temp = storage_path('app/private/legacy-import-tmp/'.Str::random(12));

        try {
            foreach ($context->mediaSource() as $attachment) {
                $this->importOne($context, $zip, $attachment, $maxSize, $temp);
            }
        } finally {
            $zip->close();
            File::deleteDirectory($temp);
        }

        $report->count(self::NAME, 'urls_mapped', $map->count());
        $report->count(self::NAME, 'conversion_jobs_queued', max(0, $this->queuedJobs() - $jobsBefore));

        if (! $context->dryRun) {
            $map->save($context->mediaMapPath());
        }
    }

    /**
     * @param  array<string, mixed>  $attachment  a media-source.json entry
     */
    private function importOne(ImportContext $context, ZipArchive $zip, array $attachment, int $maxSize, string $temp): void
    {
        $report = $context->report;
        $map = $context->media();
        $url = (string) ($attachment['url'] ?? '');
        $file = (string) ($attachment['file'] ?? '');
        $mime = (string) ($attachment['mime_type'] ?? '');
        $derivatives = array_values(array_filter((array) ($attachment['derivatives'] ?? []), is_array(...)));
        $entry = ['wp_id' => (int) ($attachment['wp_id'] ?? 0), 'file' => $file];

        if ($url === '' || $file === '') {
            $report->count(self::NAME, 'skipped_no_file');

            return;
        }

        if (Str::lower(pathinfo($file, PATHINFO_EXTENSION)) === 'zip' || $mime === 'application/zip') {
            $report->count(self::NAME, 'skipped_zip');
            $report->add(self::NAME, 'skipped_zip', $entry);

            return;
        }

        if (! in_array($mime, AttachUpload::ACCEPTED_MIMES, true)) {
            $report->count(self::NAME, 'skipped_not_image');
            $report->add(self::NAME, 'skipped_not_image', [...$entry, 'mime_type' => $mime]);

            return;
        }

        // Already imported: make sure every copy points at it, upload nothing.
        if (($assetId = $map->assetId($url)) !== null) {
            if (Asset::query()->whereKey($assetId)->exists()) {
                $this->mapAll($context, $url, $derivatives, $assetId);
                $report->count(self::NAME, 'reused');

                return;
            }

            $map->forget($assetId);
        }

        $source = $this->sourceEntry($zip, $file, $derivatives, $maxSize);

        if ($source === null) {
            $missing = ! isset($this->entries[ltrim($file, '/')]);
            $report->count(self::NAME, $missing ? 'missing' : 'too_big');
            $report->add(self::NAME, $missing ? 'missing' : 'too_big', $entry);

            return;
        }

        if ($source['file'] !== $file) {
            $report->count(self::NAME, 'resized_copy_used');
            $report->add(self::NAME, 'resized_copy_used', [...$entry, 'used' => $source['file']]);
        }

        if ($context->dryRun) {
            $report->count(self::NAME, 'would_attach');

            return;
        }

        try {
            $upload = $this->extract($zip, $source['entry'], $temp, basename($source['file']), $mime);
            $asset = Asset::query()->create([
                'title' => $this->title($attachment, $upload),
                'alt' => $this->text($attachment['alt'] ?? null),
            ]);

            try {
                ($this->attachUpload)($asset, $upload);
            } catch (Throwable $e) {
                $asset->delete();

                throw $e;
            }
        } catch (Throwable $e) {
            $report->count(self::NAME, 'failed');
            $report->add(self::NAME, 'failed', [...$entry, 'error' => $e->getMessage()]);

            return;
        }

        $this->mapAll($context, $url, $derivatives, $asset->id);
        $report->count(self::NAME, 'attached');

        // Saved as it goes, so an interrupted run resumes instead of
        // uploading everything again.
        if ($report->counts(self::NAME)['attached'] % 25 === 0) {
            $map->save($context->mediaMapPath());
        }
    }

    /**
     * @param  list<array<string, mixed>>  $derivatives
     */
    private function mapAll(ImportContext $context, string $url, array $derivatives, int $assetId): void
    {
        $map = $context->media();
        $map->put($url, $assetId);

        foreach ($derivatives as $derivative) {
            if (($derivative['url'] ?? '') !== '') {
                $map->put((string) $derivative['url'], $assetId);
                $context->report->count(self::NAME, 'derivatives_mapped');
            }
        }
    }

    /**
     * The archive entry to import: the original, or — when it is over the size
     * cap — the largest resized copy that fits. Null when neither is there.
     *
     * @param  list<array<string, mixed>>  $derivatives
     * @return array{entry: string, file: string}|null
     */
    private function sourceEntry(ZipArchive $zip, string $file, array $derivatives, int $maxSize): ?array
    {
        $name = $this->entries[ltrim($file, '/')] ?? null;

        if ($name === null) {
            return null;
        }

        $size = (int) ($zip->statName($name)['size'] ?? 0);

        if ($maxSize <= 0 || $size <= $maxSize) {
            return ['entry' => $name, 'file' => $file];
        }

        $best = null;

        foreach ($derivatives as $derivative) {
            $candidate = $this->entries[ltrim((string) ($derivative['file'] ?? ''), '/')] ?? null;
            $candidateSize = $candidate !== null ? (int) ($zip->statName($candidate)['size'] ?? 0) : 0;
            $area = (int) ($derivative['width'] ?? 0) * (int) ($derivative['height'] ?? 0);

            if ($candidate !== null && $candidateSize > 0 && $candidateSize <= $maxSize && ($best === null || $area > $best['area'])) {
                $best = ['entry' => $candidate, 'file' => (string) $derivative['file'], 'area' => $area];
            }
        }

        return $best === null ? null : ['entry' => $best['entry'], 'file' => $best['file']];
    }

    private function extract(ZipArchive $zip, string $entry, string $temp, string $name, string $mime): UploadedFile
    {
        $stream = $zip->getStream($entry);

        if ($stream === false) {
            throw new RuntimeException("Cannot read {$entry} from the archive.");
        }

        File::ensureDirectoryExists($temp);
        $path = $temp.'/'.Str::random(8).'-'.$name;
        $out = fopen($path, 'wb');

        if ($out === false) {
            fclose($stream);

            throw new RuntimeException("Cannot write {$path}.");
        }

        stream_copy_to_stream($stream, $out);
        fclose($stream);
        fclose($out);

        return new UploadedFile($path, $name, $mime, null, true);
    }

    /** Entry names under `wp-content/uploads/`, by their path relative to it. */
    private function indexEntries(ZipArchive $zip): void
    {
        $this->entries = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $position = strpos($name, self::UPLOADS);

            if ($position !== false && ! str_ends_with($name, '/')) {
                $this->entries[substr($name, $position + strlen(self::UPLOADS))] ??= $name;
            }
        }
    }

    /** @param array<string, mixed> $attachment */
    private function title(array $attachment, UploadedFile $upload): string
    {
        return $this->text($attachment['title'] ?? null) ?? AttachUpload::titleFrom($upload);
    }

    private function text(mixed $value): ?string
    {
        $text = trim(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text === '' ? null : $text;
    }

    /** Rows waiting on the database queue (the conversions), when that is the queue. */
    private function queuedJobs(): int
    {
        $connection = (string) config('queue.default');

        if (config("queue.connections.{$connection}.driver") !== 'database') {
            return 0;
        }

        $table = (string) config("queue.connections.{$connection}.table", 'jobs');

        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }
}
