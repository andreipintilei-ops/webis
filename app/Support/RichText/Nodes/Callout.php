<?php

namespace App\Support\RichText\Nodes;

use stdClass;
use Tiptap\Core\Node;

/**
 * A highlighted box around a few paragraphs: `{type: 'callout', attrs:
 * {variant: info|tip|warning}}`. The admin editor defines the same node.
 */
class Callout extends Node
{
    /** @var string */
    public static $name = 'callout';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function addAttributes(): array
    {
        return [
            'variant' => ['rendered' => false],
        ];
    }

    /**
     * @param  stdClass  $node  a decoded Tiptap node
     * @param  array<string, mixed>  $HTMLAttributes
     * @return array<int|string, mixed>
     */
    public function renderHTML($node, $HTMLAttributes = []): array
    {
        $variant = is_string($node->attrs->variant ?? null) ? $node->attrs->variant : 'info';

        return ['aside', ['class' => "callout callout-{$variant}"], 0];
    }
}
