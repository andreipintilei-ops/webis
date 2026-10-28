<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\SlugHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('remembers a renamed slug and resolves it to the current record', function () {
    $page = Page::factory()->create(['slug' => 'creare-site']);

    $page->update(['slug' => 'creare-site-prezentare']);

    expect(SlugHistory::resolve(Page::class, 'creare-site')?->is($page))->toBeTrue();
});

it('stores the morph alias, not the class name', function () {
    $post = Post::factory()->create(['slug' => 'vechi']);
    $post->update(['slug' => 'nou']);

    expect(SlugHistory::sole()->sluggable_type)->toBe('post');
});

it('keeps slug histories of different models apart', function () {
    $category = PostCategory::factory()->create(['slug' => 'noutati']);
    $category->update(['slug' => 'stiri']);

    expect(SlugHistory::resolve(Page::class, 'noutati'))->toBeNull()
        ->and(SlugHistory::resolve(PostCategory::class, 'noutati')?->is($category))->toBeTrue();
});

it('hands an old slug to whichever record used it last', function () {
    $first = Page::factory()->create(['slug' => 'oferta']);
    $first->update(['slug' => 'oferta-2025']);

    $second = Page::factory()->create(['slug' => 'oferta']);
    $second->update(['slug' => 'oferta-2026']);

    expect(SlugHistory::where('old_slug', 'oferta')->count())->toBe(1)
        ->and(SlugHistory::resolve(Page::class, 'oferta')?->is($second))->toBeTrue();
});
