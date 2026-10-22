<?php

namespace App\Support\Legacy\Import;

/**
 * What a body converter produced: the blocks (raw, as `BlockRegistry::validate()`
 * takes them — the importer validates, the converter need not), and what it
 * had to leave out, for the import report.
 */
final readonly class ConvertedBody
{
    /**
     * @param  list<array<string, mixed>>  $blocks  `[['type' => 'rich_text', 'data' => [...]], …]`
     * @param  list<string>  $warnings  human-readable, one per problem
     * @param  array<string, int>  $dropped  element or widget kind → how many were dropped
     * @param  int  $wordCount  words of visible text in the result: the "body under N words" tripwire
     */
    public function __construct(
        public array $blocks,
        public array $warnings = [],
        public array $dropped = [],
        public int $wordCount = 0,
    ) {}
}
