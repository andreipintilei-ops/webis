<?php

use App\Enums\ContentStatus;
use App\Enums\PageType;
use App\Events\ContentPublished;
use App\Models\Asset;
use App\Models\Page;
use App\Models\Project;
use App\Models\SlugHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function pagePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'type' => 'service',
        'title' => 'Creare magazin online',
        'slug' => 'creare-magazin-online',
        'excerpt' => 'Magazine online care vând.',
        'icon' => 'shopping-cart',
        'hero_asset_id' => null,
        'blocks' => [
            ['id' => 'hero-1', 'type' => 'hero', 'v' => 1, 'data' => ['heading' => 'Magazine online', 'layout' => 'split']],
        ],
        'details' => ['price_from' => 'de la 4.500 lei', 'service_type' => null],
        'sort_order' => 0,
        'project_ids' => [],
        'seo' => ['title' => null, 'description' => null, 'canonical' => null, 'noindex' => false, 'og_image_asset_id' => null, 'focus_keyword' => null],
        'status' => 'draft',
        'published_at' => null,
    ], $overrides);
}

it('lists pages, and filters them by type', function () {
    Page::factory()->ofType(PageType::Service)->count(2)->create();
    Page::factory()->ofType(PageType::Legal)->create();

    $this->get(route('admin.pages.index', ['type' => 'service']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/pages/Index')
            ->has('pages.data', 2)
            ->where('pages.data.0.type', 'service'));
});

it('opens the editor with everything the builder needs', function () {
    $page = Page::factory()->create();

    $this->get(route('admin.pages.edit', $page->id))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/pages/Edit')
            ->where('page.id', $page->id)
            ->has('blockTypes', 19)
            ->has('choices.pages')
            ->has('revisions', 1)
            ->where('titleSuffix', ' | Webis'));
});

it('creates a page with its blocks and details', function () {
    $this->post(route('admin.pages.store'), pagePayload())
        ->assertRedirect(route('admin.pages.edit', Page::sole()->id));

    $page = Page::sole();

    expect($page->type)->toBe(PageType::Service)
        ->and($page->status)->toBe(ContentStatus::Draft)
        ->and($page->blocks[0]['data']['heading'] ?? null)->toBe('Magazine online')
        ->and($page->details)->toBe(['price_from' => 'de la 4.500 lei']);
});

it('reports block errors under the block and field', function () {
    $this->post(route('admin.pages.store'), pagePayload([
        'blocks' => [['id' => 'x', 'type' => 'hero', 'v' => 1, 'data' => ['heading' => '', 'layout' => 'split']]],
    ]))->assertSessionHasErrors('blocks.0.data.heading');
});

it('refuses slugs the site routes use', function () {
    $this->post(route('admin.pages.store'), pagePayload(['slug' => 'blog']))
        ->assertSessionHasErrors('slug');
});

it('refuses a second home page', function () {
    Page::factory()->ofType(PageType::Home)->create(['slug' => 'acasa']);

    $this->post(route('admin.pages.store'), pagePayload(['type' => 'home', 'slug' => 'alta-acasa']))
        ->assertSessionHasErrors('type');
});

it('schedules only for the future, and publishes only for the past', function () {
    $this->post(route('admin.pages.store'), pagePayload(['status' => 'scheduled', 'published_at' => '2020-01-01T10:00']))
        ->assertSessionHasErrors('published_at');

    $this->post(route('admin.pages.store'), pagePayload(['status' => 'published', 'published_at' => now()->addYear()->format('Y-m-d\TH:i')]))
        ->assertSessionHasErrors('published_at');
});

it('reads schedule times as Romanian local time', function () {
    $local = now('Europe/Bucharest')->addDay()->setTime(9, 30);

    $this->post(route('admin.pages.store'), pagePayload([
        'status' => 'scheduled',
        'published_at' => $local->format('Y-m-d\TH:i'),
    ]))->assertSessionHasNoErrors();

    expect(Page::sole()->published_at?->toIso8601String())->toBe($local->utc()->toIso8601String());
});

it('announces a page when it goes live, but not on later saves', function () {
    Event::fake([ContentPublished::class]);
    $page = Page::factory()->draft()->create(['slug' => 'despre']);

    $this->put(route('admin.pages.update', $page->id), pagePayload(['slug' => 'despre', 'status' => 'published']))
        ->assertRedirect();
    $this->put(route('admin.pages.update', $page->id), pagePayload(['slug' => 'despre', 'status' => 'published', 'title' => 'Altul']))
        ->assertRedirect();

    Event::assertDispatchedTimes(ContentPublished::class, 1);
});

it('links related projects in the chosen order', function () {
    [$first, $second] = Project::factory()->count(2)->create();

    $this->post(route('admin.pages.store'), pagePayload(['project_ids' => [$second->id, $first->id]]));

    expect(Page::sole()->projects->pluck('id')->all())->toBe([$second->id, $first->id]);
});

it('keeps the old URL working after a slug change', function () {
    $page = Page::factory()->create(['slug' => 'vechi']);

    $this->put(route('admin.pages.update', $page->id), pagePayload(['slug' => 'nou']))->assertRedirect();

    expect(SlugHistory::resolve(Page::class, 'vechi')?->is($page))->toBeTrue();
});

it('tracks images chosen for the page and its blocks', function () {
    [$card, $heroImage] = Asset::factory()->count(2)->create();

    $this->post(route('admin.pages.store'), pagePayload([
        'hero_asset_id' => $card->id,
        'blocks' => [['id' => 'h', 'type' => 'hero', 'v' => 1, 'data' => ['heading' => 'X', 'layout' => 'split', 'image_asset_id' => $heroImage->id]]],
    ]))->assertSessionHasNoErrors();

    expect($card->usages()->count())->toBe(1)->and($heroImage->usages()->count())->toBe(1);
});

it('moves pages to the trash, restores them and deletes them for good', function () {
    $page = Page::factory()->create();

    $this->delete(route('admin.pages.destroy', $page->id))->assertRedirect(route('admin.pages.index'));
    expect($page->fresh()?->trashed())->toBeTrue();

    $this->post(route('admin.pages.restore', $page->id))->assertRedirect(route('admin.pages.edit', $page->id));
    expect($page->fresh()?->trashed())->toBeFalse();

    $page->delete();
    $this->delete(route('admin.pages.force-destroy', $page->id))->assertRedirect();
    expect(Page::withTrashed()->find($page->id))->toBeNull();
});

it('saves the order pages are dragged into', function () {
    [$a, $b, $c] = Page::factory()->ofType(PageType::Service)->count(3)->create();

    $this->post(route('admin.pages.reorder'), ['ids' => [$c->id, $a->id, $b->id]])->assertRedirect();

    expect([$c->fresh()?->sort_order, $a->fresh()?->sort_order, $b->fresh()?->sort_order])->toBe([0, 1, 2]);
});

it('restores an earlier revision', function () {
    $page = Page::factory()->create(['title' => 'Prima']);
    $page->update(['title' => 'A doua']);
    $first = $page->revisions()->get()->last();

    $this->post(route('admin.revisions.restore', $first))
        ->assertRedirect(route('admin.pages.edit', $page->id));

    expect($page->fresh()?->title)->toBe('Prima');
});
