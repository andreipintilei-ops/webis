<?php

namespace App\Console\Commands;

use App\Enums\ContentStatus;
use App\Events\ContentPublished;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Console\Command;

/**
 * Flips Scheduled items whose `published_at` has passed to Published. Runs
 * every minute from the scheduler, so a post goes live within a minute of its
 * time (or the cron interval, where the host allows no less).
 */
class PublishDueContent extends Command
{
    protected $signature = 'content:publish-due';

    protected $description = 'Publish scheduled pages, projects and posts whose time has come';

    public function handle(): int
    {
        $published = 0;

        foreach ([Page::class, Project::class, Post::class] as $model) {
            // Saved one by one (not a mass update) so model events fire: slug
            // history, revisions and asset usages stay consistent.
            $model::query()->dueForPublishing()->each(function (Page|Project|Post $item) use (&$published): void {
                $item->status = ContentStatus::Published;
                $item->save();

                ContentPublished::dispatch($item);
                $published++;
            });
        }

        if ($published > 0) {
            $this->info("Published {$published} item(s).");
        }

        return self::SUCCESS;
    }
}
