<?php

namespace App\Support\Legacy;

use RuntimeException;
use ZipArchive;

/**
 * The upload files present in the backup of the old site's `public_html`,
 * read from the zip's index alone: nothing is extracted. Paths are relative to
 * `wp-content/uploads/`, the form `_wp_attached_file` uses.
 */
final class UploadsArchive
{
    private const string UPLOADS = 'wp-content/uploads/';

    /** @var array<string, true> */
    private array $files = [];

    /** @param iterable<string> $names entry names as the archive lists them */
    public function __construct(iterable $names)
    {
        foreach ($names as $name) {
            $position = strpos($name, self::UPLOADS);

            if ($position !== false && ! str_ends_with($name, '/')) {
                $this->files[substr($name, $position + strlen(self::UPLOADS))] = true;
            }
        }
    }

    public static function fromZip(string $path): self
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException("Cannot open the archive {$path}.");
        }

        $names = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = (string) $zip->getNameIndex($i);
        }

        $zip->close();

        return new self($names);
    }

    public function has(string $relativePath): bool
    {
        return isset($this->files[ltrim($relativePath, '/')]);
    }

    public function count(): int
    {
        return count($this->files);
    }
}
