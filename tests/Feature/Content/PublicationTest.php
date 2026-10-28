<?php

use App\Enums\ContentStatus;
use App\Events\ContentPublished;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('only treats published items whose date has passed as public', function () {
    $live = Page::factory()->published()->create();
    Page::factory()->draft()->create();
    Page::factory()->scheduled()->create();

    // Published but dated in the future must not leak early.
    Page::factory()->published(now()->addHour())->create();

    expect(Page::published()->pluck('id')->all())->toBe([$live->id]);
});

it('stamps published_at when an item is published without a date', function () {
    $this->freezeTime();

    $post = Post::factory()->draft()->create();
    $post->update(['status' => ContentStatus::Published]);

    expect($post->fresh()->published_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($post->fresh()->isPublished())->toBeTrue();
});

it('publishes scheduled items that are due and announces each one', function () {
    Event::fake([ContentPublished::class]);

    $duePage = Page::factory()->scheduled(now()->subMinute())->create();
    $dueProject = Project::factory()->scheduled(now()->subMinute())->create();
    $duePost = Post::factory()->scheduled(now()->subMinute())->create();
    $later = Post::factory()->scheduled(now()->addHour())->create();

    $this->artisan('content:publish-due')->assertSuccessful();

    expect($duePage->fresh()->status)->toBe(ContentStatus::Published)
        ->and($dueProject->fresh()->status)->toBe(ContentStatus::Published)
        ->and($duePost->fresh()->status)->toBe(ContentStatus::Published)
        ->and($later->fresh()->status)->toBe(ContentStatus::Scheduled);

    Event::assertDispatchedTimes(ContentPublished::class, 3);
    Event::assertDispatched(fn (ContentPublished $event) => $event->content->is($duePost));
});

it('keeps the scheduled publishing time rather than the moment the command ran', function () {
    $at = now()->subMinutes(3)->startOfSecond();
    $post = Post::factory()->scheduled($at)->create();

    $this->artisan('content:publish-due')->assertSuccessful();

    expect($post->fresh()->published_at?->equalTo($at))->toBeTrue();
});
