<?php

namespace App\Support\Legacy;

/**
 * One converted body: blocks that pass `BlockRegistry::validate()`, and the
 * report of what was dropped or left unresolved on the way.
 *
 * @phpstan-import-type StoredBlock from \App\Blocks\BlockRegistry
 */
final readonly class Conversion
{
    /**
     * @param  list<StoredBlock>  $blocks
     */
    public function __construct(
        public array $blocks,
        public ConversionReport $report,
    ) {}

    /**
     * @return list<string>
     */
    public function blockTypes(): array
    {
        return array_column($this->blocks, 'type');
    }
}
