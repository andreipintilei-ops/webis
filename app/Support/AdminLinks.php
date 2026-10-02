<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;

/**
 * How the admin names and links to a record from elsewhere — the media
 * library's "used in" list, the dashboard, search results.
 */
final class AdminLinks
{
    /**
     * @return array{type: string, title: string, url: string|null}
     */
    public static function describe(Model $model): array
    {
        return match (true) {
            $model instanceof Page => ['type' => 'Pagină', 'title' => $model->title, 'url' => route('admin.pages.edit', $model->id)],
            $model instanceof Project => ['type' => 'Proiect', 'title' => $model->title, 'url' => route('admin.projects.edit', $model->id)],
            $model instanceof Post => ['type' => 'Articol', 'title' => $model->title, 'url' => route('admin.posts.edit', $model->id)],
            // Clients and testimonials are edited in a panel on their list page.
            $model instanceof Client => ['type' => 'Client', 'title' => $model->name, 'url' => route('admin.clients.index', ['edit' => $model->id])],
            $model instanceof Testimonial => ['type' => 'Testimonial', 'title' => $model->author_name, 'url' => route('admin.testimonials.index', ['edit' => $model->id])],
            default => ['type' => class_basename($model), 'title' => '#'.$model->getKey(), 'url' => null],
        };
    }
}
