<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Blocks\Block;
use App\Blocks\BlockRegistry;
use App\Enums\ContentStatus;
use App\Events\ContentPublished;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Revision;
use App\Settings\SeoSettings;
use App\Support\AdminTime;

/**
 * Props and bookkeeping shared by the page, project and post editors.
 */
trait EditsContent
{
    /**
     * @return list<array{value: string, label: string}>
     */
    protected function statusOptions(): array
    {
        return array_map(
            fn (ContentStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
            ContentStatus::cases(),
        );
    }

    /**
     * @return list<array{type: string, label: string, version: int, defaults: array<string, mixed>}>
     */
    protected function blockTypes(): array
    {
        return array_values(array_map(
            fn (Block $block): array => [
                'type' => $block::type(),
                'label' => $block::label(),
                'version' => $block::version(),
                'defaults' => $block->defaults(),
            ],
            app(BlockRegistry::class)->all(),
        ));
    }

    /**
     * @return list<array{id: int, user: string|null, created_at: string|null}>
     */
    protected function revisionsFor(Page|Project|Post $content): array
    {
        if (! $content->exists) {
            return [];
        }

        return array_values($content->revisions()->with('user:id,name')->get()
            ->map(fn (Revision $revision): array => [
                'id' => $revision->id,
                'user' => $revision->user?->name,
                'created_at' => AdminTime::display($revision->created_at),
            ])
            ->all());
    }

    /**
     * Fire ContentPublished when a save made the item live — not on every save
     * of an already-live item.
     */
    protected function announceIfNewlyPublished(Page|Project|Post $content, bool $wasPublished): void
    {
        if (! $wasPublished && $content->isPublished()) {
            ContentPublished::dispatch($content);
        }
    }

    /**
     * What every <title> ends with, for the SEO panel's preview.
     */
    protected function titleSuffix(): string
    {
        return app(SeoSettings::class)->title_suffix;
    }

    /**
     * @return array{status: string, published_at: string|null, is_live: bool}
     */
    protected function publicationPayload(Page|Project|Post $content): array
    {
        return [
            'status' => $content->exists ? $content->status->value : ContentStatus::Draft->value,
            'published_at' => AdminTime::toInput($content->published_at),
            'is_live' => $content->exists && $content->isPublished(),
        ];
    }
}
