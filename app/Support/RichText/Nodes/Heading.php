<?php

namespace App\Support\RichText\Nodes;

use Tiptap\Nodes\Heading as BaseHeading;

/**
 * Headings with a stable `id` (assigned by RichText::sanitize) so the table of
 * contents can link to them. h1 is the page title, so the body starts at h2.
 */
class Heading extends BaseHeading
{
    /**
     * @return array<string, mixed>
     */
    public function addOptions(): array
    {
        return [
            'levels' => [2, 3, 4],
            'HTMLAttributes' => [],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function addAttributes(): array
    {
        return [
            'id' => [],
        ];
    }
}
