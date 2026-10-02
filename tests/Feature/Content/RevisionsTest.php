<?php

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('snapshots content on create and on every content change', function () {
    $this->actingAs($editor = User::factory()->create());

    $page = Page::factory()->create(['title' => 'Prima versiune']);
    $page->update(['title' => 'A doua versiune']);

    expect($page->revisions()->count())->toBe(2)
        ->and($page->revisions()->first()?->data['title'])->toBe('A doua versiune')
        ->and($page->revisions()->first()?->user_id)->toBe($editor->id);
});

it('does not snapshot changes outside the content, like publishing', function () {
    $page = Page::factory()->draft()->create();

    $page->update(['status' => ContentStatus::Published, 'sort_order' => 5]);

    expect($page->revisions()->count())->toBe(1);
});

it('keeps only the newest revisions per item', function () {
    $page = Page::factory()->create();

    foreach (range(1, Revision::KEEP + 5) as $i) {
        $page->update(['title' => "Versiunea {$i}"]);
    }

    expect($page->revisions()->count())->toBe(Revision::KEEP)
        ->and($page->revisions()->first()?->data['title'])->toBe('Versiunea '.(Revision::KEEP + 5));
});

it('restores an earlier snapshot, including JSON columns', function () {
    $page = Page::factory()->create([
        'title' => 'Original',
        'blocks' => [['id' => 'a', 'type' => 'hero', 'v' => 1, 'data' => ['heading' => 'Salut']]],
        'seo' => ['title' => 'Titlu SEO'],
    ]);
    $original = $page->revisions()->firstOrFail();

    $page->update(['title' => 'Stricat', 'blocks' => [], 'seo' => null]);
    $page->restoreRevision($original);
    $page->refresh();

    expect($page->title)->toBe('Original')
        ->and($page->blocks[0]['data']['heading'] ?? null)->toBe('Salut')
        ->and($page->seo->title)->toBe('Titlu SEO')
        // The restore is itself a revision, so it can be undone.
        ->and($page->revisions()->count())->toBe(3);
});

it('keeps revisions of a trashed item and drops them on force delete', function () {
    $page = Page::factory()->create();

    $page->delete();
    expect(Revision::count())->toBe(1);

    $page->forceDelete();
    expect(Revision::count())->toBe(0);
});
