<?php

namespace App\Support;

use App\Enums\PageType;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\ProjectCategory;

/**
 * Public paths of content, before the public routes exist (Phase 3 registers
 * them with these same shapes). Relative, so they work on any host.
 */
final class PublicUrls
{
    public static function for(Page|Project|Post|ProjectCategory|PostCategory $model): string
    {
        return match (true) {
            $model instanceof Page => match ($model->type) {
                PageType::Home => '/',
                // An article keeps the old site's root address: no /blog/ prefix.
                PageType::Article => '/'.$model->slug,
                default => '/'.$model->slug,
            },
            $model instanceof Project => '/proiect/'.$model->slug,
            $model instanceof Post => '/blog/'.$model->slug,
            $model instanceof ProjectCategory => '/portofoliu/categorie/'.$model->slug,
            $model instanceof PostCategory => '/blog/categorie/'.$model->slug,
        };
    }
}
