<?php

namespace App\Events;

use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A page, project or post just went live — by the scheduler or from the admin.
 * Listeners (later phases) clear the response cache, rebuild the sitemap and
 * ping IndexNow.
 */
class ContentPublished
{
    use Dispatchable;

    public function __construct(
        public readonly Page|Project|Post $content,
    ) {}
}
