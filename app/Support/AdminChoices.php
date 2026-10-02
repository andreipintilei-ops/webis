<?php

namespace App\Support;

use App\Enums\PageType;
use App\Models\Client;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Testimonial;
use Illuminate\Support\Collection;

/**
 * Every record a block or editor field can point at, as `{id, label, url?}`,
 * sent with each edit page so pickers need no extra requests. Drafts are
 * included — an editor may build pages that link to each other before launch.
 */
final class AdminChoices
{
    /**
     * @return array<string, list<array{id: int, label: string, url?: string}>>
     */
    public static function all(): array
    {
        $pages = Page::query()->orderBy('type')->orderBy('sort_order')->orderBy('title')
            ->get(['id', 'type', 'title', 'slug']);

        return [
            'pages' => self::list($pages->map(fn (Page $page): array => [
                'id' => $page->id,
                'label' => $page->title.' ('.$page->type->label().')',
                'url' => PublicUrls::for($page),
            ])),
            'servicePages' => self::list($pages
                ->filter(fn (Page $page): bool => in_array($page->type, [PageType::Service, PageType::Industry], true))
                ->map(fn (Page $page): array => ['id' => $page->id, 'label' => $page->title, 'url' => PublicUrls::for($page)])),
            'projects' => self::list(Project::query()->orderBy('sort_order')->orderBy('title')->get(['id', 'title', 'slug'])
                ->map(fn (Project $project): array => ['id' => $project->id, 'label' => $project->title, 'url' => PublicUrls::for($project)])),
            'projectCategories' => self::list(ProjectCategory::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
                ->map(fn (ProjectCategory $category): array => ['id' => $category->id, 'label' => $category->name])),
            'clients' => self::list(Client::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
                ->map(fn (Client $client): array => ['id' => $client->id, 'label' => $client->name])),
            'testimonials' => self::list(Testimonial::query()->orderBy('sort_order')->get(['id', 'author_name', 'company'])
                ->map(fn (Testimonial $testimonial): array => [
                    'id' => $testimonial->id,
                    'label' => $testimonial->author_name.($testimonial->company ? ', '.$testimonial->company : ''),
                ])),
            'posts' => self::list(Post::query()->latest('published_at')->get(['id', 'title', 'slug'])
                ->map(fn (Post $post): array => ['id' => $post->id, 'label' => $post->title, 'url' => PublicUrls::for($post)])),
            'postCategories' => self::list(PostCategory::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
                ->map(fn (PostCategory $category): array => ['id' => $category->id, 'label' => $category->name])),
        ];
    }

    /**
     * A JSON array, never an object — a filtered collection keeps gaps in its
     * keys, which would serialise as `{"0":…,"3":…}`.
     *
     * @template T of array{id: int, label: string, url?: string}
     *
     * @param  Collection<int, T>  $choices
     * @return list<T>
     */
    private static function list(Collection $choices): array
    {
        return array_values($choices->all());
    }
}
