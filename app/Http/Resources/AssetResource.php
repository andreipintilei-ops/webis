<?php

namespace App\Http\Resources;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An asset as the admin sees it: grid thumbnail, details panel and picker.
 *
 * @mixin Asset
 */
class AssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $file = $this->file();

        // Conversions run on the queue, so right after an upload the thumbnail
        // may not exist yet; the original stands in until it does.
        $thumbUrl = $file === null ? null : ($file->hasGeneratedConversion('thumb') ? $file->getUrl('thumb') : $file->getUrl());

        return [
            'id' => $this->id,
            'title' => $this->title,
            'alt' => $this->alt,
            'caption' => $this->caption,
            'width' => $this->width,
            'height' => $this->height,
            'url' => $file?->getUrl(),
            'thumb_url' => $thumbUrl,
            'file_name' => $file?->file_name,
            'mime_type' => $file?->mime_type,
            'size' => $file?->size,
            'usages_count' => $this->whenCounted('usages'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
