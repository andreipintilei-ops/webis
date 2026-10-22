<?php

use App\Models\NotFoundLog;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs a first miss with one hit and its referrer and user agent', function () {
    $this->withHeaders(['Referer' => 'https://google.com/', 'User-Agent' => 'Mozilla/5.0'])
        ->get('/pagina-pierduta/')
        ->assertNotFound();

    $log = NotFoundLog::query()->sole();
    expect($log->path)->toBe('/pagina-pierduta')
        ->and($log->hits)->toBe(1)
        ->and($log->last_seen_at)->not->toBeNull()
        ->and($log->last_referrer)->toBe('https://google.com/')
        ->and($log->last_user_agent)->toBe('Mozilla/5.0')
        ->and($log->is_ignored)->toBeFalse();
});

it('counts a repeat miss on the same row', function () {
    $this->get('/pagina-pierduta')->assertNotFound();
    $this->withHeaders(['Referer' => 'https://bing.com/'])->get('/Pagina-Pierduta/?x=1')->assertNotFound();

    $log = NotFoundLog::query()->sole();
    expect($log->hits)->toBe(2)
        ->and($log->last_referrer)->toBe('https://bing.com/');
});

it('never logs a path that redirected', function () {
    Redirect::factory()->create(['source_path' => '/vechi', 'target' => '/nou']);

    $this->get('/vechi')->assertStatus(301);

    expect(NotFoundLog::query()->count())->toBe(0);
});

it('never logs api or JSON misses', function () {
    $this->get('/api/nu-exista')->assertNotFound();
    $this->getJson('/pagina-pierduta')->assertNotFound();

    expect(NotFoundLog::query()->count())->toBe(0);
});

it('never logs scanner noise', function (string $path) {
    $this->get($path)->assertNotFound();

    expect(NotFoundLog::query()->count())->toBe(0);
})->with([
    '/wp-login.php',
    '/xmlrpc.php',
    '/wp-admin/install',
    '/wp-content/uploads/2020/01/poza.jpg',
    '/wp-includes/js/jquery.js',
    '/wp-json/wp/v2/users',
    '/.env',
    '/.git/config',
    '/backup.sql',
    '/site.bak',
    '/default.aspx',
    '/test.cgi',
    '/pagina%20cu%20spatii',
    '/'.str_repeat('a', 300),
]);

it('truncates the referrer and user agent to 512 characters', function () {
    $this->withHeaders([
        'Referer' => 'https://example.com/'.str_repeat('r', 1000),
        'User-Agent' => str_repeat('u', 1000),
    ])->get('/pagina-pierduta')->assertNotFound();

    $log = NotFoundLog::query()->sole();
    expect(mb_strlen($log->last_referrer))->toBe(512)
        ->and(mb_strlen($log->last_user_agent))->toBe(512);
});

it('keeps an ignored path ignored on a new hit', function () {
    NotFoundLog::factory()->create(['path' => '/ignorata', 'hits' => 4, 'is_ignored' => true]);

    $this->get('/ignorata')->assertNotFound();

    $log = NotFoundLog::query()->sole();
    expect($log->hits)->toBe(5)
        ->and($log->is_ignored)->toBeTrue();
});
