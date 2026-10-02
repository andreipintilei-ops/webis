<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Admin\Concerns\EditsContent;
use App\Http\Controllers\Admin\Concerns\ManagesTrash;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use App\Support\AdminChoices;
use App\Support\AdminTime;
use App\Support\PublicUrls;
use App\Support\RichText\RichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    use EditsContent, ManagesTrash;

    /**
     * GET /admin/blog
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = ContentStatus::tryFrom((string) $request->query('status', ''));
        $category = (int) $request->query('category', 0);
        $trashed = $request->boolean('trashed');

        $posts = Post::query()
            ->with(['category:id,name', 'author:id,name'])
            ->when($trashed, fn (Builder $query) => $query->onlyTrashed())
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $where) => $where
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")))
            ->when($status, fn (Builder $query, ContentStatus $status) => $query->where('status', $status->value))
            ->when($category > 0, fn (Builder $query) => $query->where('post_category_id', $category))
            // Drafts first (no date), then newest.
            ->orderByRaw('published_at is not null')
            ->latest('published_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Post $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'url' => PublicUrls::for($post),
                'category' => $post->category?->name,
                'author' => $post->author?->name,
                'reading_minutes' => $post->reading_minutes,
                'status' => $post->status->value,
                'status_label' => $post->status->label(),
                'is_live' => $post->isPublished(),
                'published_at' => AdminTime::display($post->published_at),
                'trashed' => $post->trashed(),
            ]);

        return Inertia::render('admin/posts/Index', [
            'posts' => $posts,
            'filters' => [
                'q' => $search,
                'status' => $status->value ?? '',
                'category' => $category > 0 ? (string) $category : '',
                'trashed' => $trashed,
            ],
            'statuses' => $this->statusOptions(),
            'categories' => PostCategory::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
                ->map(fn (PostCategory $c): array => ['value' => (string) $c->id, 'label' => $c->name])
                ->all(),
        ]);
    }

    /**
     * GET /admin/blog/creeaza — the signed-in editor is the default author.
     */
    public function create(Request $request): Response
    {
        return $this->editor(new Post([
            'title' => '',
            'slug' => '',
            'body' => RichText::emptyDocument(),
            'author_id' => $request->user()?->id,
        ]));
    }

    /**
     * POST /admin/blog
     */
    public function store(PostRequest $request): RedirectResponse
    {
        $post = Post::create($request->postAttributes());

        $this->announceIfNewlyPublished($post, false);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Articolul a fost creat.']);

        return to_route('admin.posts.edit', $post->id);
    }

    /**
     * GET /admin/blog/{post}/editeaza
     */
    public function edit(Post $post): Response
    {
        return $this->editor($post);
    }

    /**
     * PUT /admin/blog/{post}
     */
    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $wasPublished = $post->isPublished();

        $post->update($request->postAttributes());

        $this->announceIfNewlyPublished($post, $wasPublished);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modificările au fost salvate.']);

        return to_route('admin.posts.edit', $post->id);
    }

    public function destroy(Post $post): RedirectResponse
    {
        return $this->trash($post);
    }

    public function restore(Post $post): RedirectResponse
    {
        return $this->untrash($post);
    }

    public function forceDestroy(Post $post): RedirectResponse
    {
        return $this->purge($post);
    }

    protected function routePrefix(): string
    {
        return 'posts';
    }

    protected function trashMessages(): array
    {
        return [
            'trashed' => 'Articolul a fost mutat în coș.',
            'restored' => 'Articolul a fost restaurat.',
            'purged' => 'Articolul a fost șters definitiv.',
        ];
    }

    private function editor(Post $post): Response
    {
        return Inertia::render('admin/posts/Edit', [
            'post' => [
                'id' => $post->exists ? $post->id : null,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'body' => $post->body ?? RichText::emptyDocument(),
                'cover_asset_id' => $post->cover_asset_id,
                'post_category_id' => $post->post_category_id,
                'author_id' => $post->author_id,
                'cta_page_id' => $post->cta_page_id,
                'is_featured' => (bool) $post->is_featured,
                'reading_minutes' => $post->reading_minutes ?? 0,
                'seo' => $post->seo->toArray(),
                'public_url' => $post->exists ? PublicUrls::for($post) : null,
                ...$this->publicationPayload($post),
            ],
            'statuses' => $this->statusOptions(),
            'choices' => AdminChoices::all(),
            'authors' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => $user->name])
                ->all(),
            'revisions' => $this->revisionsFor($post),
            'titleSuffix' => $this->titleSuffix(),
        ]);
    }
}
