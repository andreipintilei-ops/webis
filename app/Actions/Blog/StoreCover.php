<?php

namespace App\Actions\Blog;

use App\Listeners\FlattenCoverShareImage;
use App\Models\Page;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Sets an article's cover from an image file — uploaded in the admin, or
 * one from a folder (blog:import-covers) — replacing the one it had (the
 * collection holds one). The sizes and the share image are made at once
 * (Page::registerMediaConversions). `alt`, when given, becomes its alt
 * text; left null, the article keeps the one it had.
 */
final class StoreCover
{
    public function __invoke(Page $page, UploadedFile|string $file, ?string $alt = null): Media
    {
        $extension = strtolower($file instanceof UploadedFile ? $file->getClientOriginalExtension() : pathinfo($file, PATHINFO_EXTENSION));

        $adder = $file instanceof UploadedFile ? $page->addMedia($file) : $page->addMedia($file)->preservingOriginal();

        // A cut-out (a transparent ground) is kept whole, not cropped, so it
        // can sit in its frame with room around it (Page::registerMediaConversions).
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $image = FlattenCoverShareImage::open($path);

        $media = $adder
            ->withCustomProperties([
                'transparent' => $image !== null && FlattenCoverShareImage::isTransparent($image),
                'width' => $image !== null ? imagesx($image) : null,
                'height' => $image !== null ? imagesy($image) : null,
            ])
            ->usingName($page->title)
            ->usingFileName($page->slug.'.'.($extension === 'jpeg' ? 'jpg' : $extension))
            ->toMediaCollection(Page::COVER);

        if ($alt !== null) {
            $page->forceFill(['cover_alt' => $alt])->saveQuietly();
        }

        return $media;
    }
}
