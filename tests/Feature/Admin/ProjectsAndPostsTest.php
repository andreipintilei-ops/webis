<?php

use App\Enums\ContentStatus;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\SlugHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs($this->editor = User::factory()->create());
});

/**
 * @return array<string, mixed>
 */
function noSeo(): array
{
    return ['title' => null, 'description' => null, 'canonical' => null, 'noindex' => false, 'og_image_asset_id' => null, 'focus_keyword' => null];
}

// ---- Projects -------------------------------------------------------------

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function projectPayload(array $overrides = []): array
{
    return array_replace([
        'title' => 'Magazin online Florăria Iris',
        'slug' => 'magazin-online-floraria-iris',
        'client_id' => null,
        'year' => 2025,
        'url' => 'https://floraria-iris.ro',
        'summary' => 'Magazin WooCommerce migrat pe Laravel.',
        'cover_asset_id' => null,
        'blocks' => [],
        'metrics' => [['value' => '+180%', 'label' => 'comenzi online']],
        'is_featured' => true,
        'sort_order' => 0,
        'category_ids' => [],
        'seo' => noSeo(),
        'status' => 'published',
        'published_at' => null,
    ], $overrides);
}

it('lists projects with their client and categories', function () {
    $project = Project::factory()->create(['client_id' => Client::factory()->create(['name' => 'Iris'])->id]);
    $project->categories()->attach(ProjectCategory::factory()->create(['name' => 'Magazine online']));

    $this->get(route('admin.projects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/projects/Index')
            ->where('projects.data.0.client', 'Iris')
            ->where('projects.data.0.categories', ['Magazine online']));
});

it('creates a published project with metrics and categories', function () {
    $category = ProjectCategory::factory()->create();
    $cover = Asset::factory()->create();

    $this->post(route('admin.projects.store'), projectPayload([
        'category_ids' => [$category->id],
        'cover_asset_id' => $cover->id,
    ]))->assertSessionHasNoErrors();

    $project = Project::sole();

    expect($project->isPublished())->toBeTrue()
        // MySQL stores JSON with its own key order, so compare by content.
        ->and($project->metrics)->toEqual([['value' => '+180%', 'label' => 'comenzi online']])
        ->and($project->categories->pluck('id')->all())->toBe([$category->id])
        ->and($cover->usages()->count())->toBe(1);
});

it('validates project metrics and the live site address', function () {
    $this->post(route('admin.projects.store'), projectPayload([
        'url' => 'nu-e-adresa',
        'metrics' => [['value' => '', 'label' => 'x']],
    ]))->assertSessionHasErrors(['url', 'metrics.0.value']);
});

it('opens the project editor', function () {
    $project = Project::factory()->create();

    $this->get(route('admin.projects.edit', $project->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/projects/Edit')
            ->where('project.id', $project->id)
            ->has('blockTypes', 19));
});

it('trashes and restores a project', function () {
    $project = Project::factory()->create();

    $this->delete(route('admin.projects.destroy', $project->id))->assertRedirect(route('admin.projects.index'));
    $this->post(route('admin.projects.restore', $project->id))->assertRedirect(route('admin.projects.edit', $project->id));

    expect($project->fresh()?->trashed())->toBeFalse();
});

// ---- Posts ----------------------------------------------------------------

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function postPayload(array $overrides = []): array
{
    return array_replace([
        'title' => 'Cât costă un magazin online în 2026',
        'slug' => 'cat-costa-un-magazin-online',
        'excerpt' => 'Prețuri reale, pe categorii.',
        'body' => ['type' => 'doc', 'content' => [
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Pe scurt']]],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Depinde de câte produse ai.']]],
        ]],
        'cover_asset_id' => null,
        'post_category_id' => null,
        'author_id' => null,
        'cta_page_id' => null,
        'is_featured' => false,
        'seo' => noSeo(),
        'status' => 'draft',
        'published_at' => null,
    ], $overrides);
}

it('defaults a new post to the signed-in author', function () {
    $this->get(route('admin.posts.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/posts/Edit')
            ->where('post.author_id', $this->editor->id)
            ->where('post.body.type', 'doc'));
});

it('creates a post and renders its body', function () {
    $service = Page::factory()->create();
    $category = PostCategory::factory()->create();

    $this->post(route('admin.posts.store'), postPayload([
        'cta_page_id' => $service->id,
        'post_category_id' => $category->id,
        'author_id' => $this->editor->id,
    ]))->assertSessionHasNoErrors();

    $post = Post::sole();

    expect($post->status)->toBe(ContentStatus::Draft)
        ->and($post->body_html)->toContain('<h2 id="pe-scurt">Pe scurt</h2>')
        ->and($post->toc)->toBe([['id' => 'pe-scurt', 'text' => 'Pe scurt', 'level' => 2]])
        ->and($post->ctaPage?->is($service))->toBeTrue();
});

it('requires a body document', function () {
    $this->post(route('admin.posts.store'), postPayload(['body' => null]))
        ->assertSessionHasErrors('body');
});

it('lists drafts before published posts', function () {
    Post::factory()->published()->create(['title' => 'Publicat']);
    Post::factory()->draft()->create(['title' => 'Ciornă']);

    $this->get(route('admin.posts.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('posts.data.0.title', 'Ciornă')
            ->where('posts.data.1.title', 'Publicat'));
});

// ---- Categories -----------------------------------------------------------

it('manages portfolio categories on one page', function () {
    $this->get(route('admin.project-categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/categories/Index')
            ->where('urlPrefix', '/clienti/categorie/'));

    $this->post(route('admin.project-categories.store'), [
        'name' => 'Magazine online',
        'slug' => 'magazine-online',
        'description' => null,
        'sort_order' => 0,
        'seo' => ['title' => 'Portofoliu magazine online', 'description' => null, 'noindex' => false],
    ])->assertSessionHasNoErrors()->assertRedirect();

    $category = ProjectCategory::sole();
    expect($category->seo->title)->toBe('Portofoliu magazine online');

    $this->put(route('admin.project-categories.update', $category->id), [
        'name' => 'Magazine online',
        'slug' => 'ecommerce',
        'sort_order' => 1,
    ])->assertSessionHasNoErrors();

    expect(SlugHistory::resolve(ProjectCategory::class, 'magazine-online')?->is($category))->toBeTrue();

    $this->delete(route('admin.project-categories.destroy', $category->id))->assertRedirect();
    expect(ProjectCategory::count())->toBe(0);
});

it('keeps posts when their blog category is deleted', function () {
    $category = PostCategory::factory()->create();
    $post = Post::factory()->create(['post_category_id' => $category->id]);

    $this->delete(route('admin.post-categories.destroy', $category->id))->assertRedirect();

    expect($post->fresh()?->post_category_id)->toBeNull();
});
