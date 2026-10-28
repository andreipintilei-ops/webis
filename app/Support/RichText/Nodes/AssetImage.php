<?php

namespace App\Support\RichText\Nodes;

use stdClass;
use Tiptap\Core\Node;

/**
 * An image from the media library, inside rich text: `{type: 'assetImage',
 * attrs: {assetId, caption}}`. The admin editor defines the same node.
 *
 * Rendered as a placeholder <figure data-asset-id>. The real <picture> (srcset,
 * width/height, alt from the asset) is filled in at display time by
 * <x-rich-text>, because conversions are generated on the queue and may not
 * exist yet when the body is saved.
 */
class AssetImage extends Node
{
    /** @var string */
    public static $name = 'assetImage';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function addAttributes(): array
    {
        return [
            'assetId' => ['rendered' => false],
            'caption' => ['rendered' => false],
        ];
    }

    /**
     * @param  stdClass  $node  a decoded Tiptap node
     * @param  array<string, mixed>  $HTMLAttributes
     * @return array<int|string, mixed>
     */
    public function renderHTML($node, $HTMLAttributes = []): array
    {
        $assetId = (int) ($node->attrs->assetId ?? 0);
        $caption = is_string($node->attrs->caption ?? null) ? $node->attrs->caption : '';

        $figcaption = $caption !== ''
            ? '<figcaption>'.htmlspecialchars($caption, ENT_QUOTES, 'UTF-8').'</figcaption>'
            : '';

        return ['content' => '<figure class="rt-image" data-asset-id="'.$assetId.'">'.$figcaption.'</figure>'];
    }
}
