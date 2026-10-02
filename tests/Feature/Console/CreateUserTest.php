<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('creates an admin with the given password', function () {
    $this->artisan('user:create', [
        'email' => 'Andrei@Webis.ro',
        '--name' => 'Andrei',
        '--password' => 'secret-password-123',
    ])->assertSuccessful();

    $user = User::sole();

    expect($user->email)->toBe('andrei@webis.ro')
        ->and($user->name)->toBe('Andrei')
        ->and($user->role)->toBe(UserRole::Admin)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('secret-password-123', $user->password))->toBeTrue();
});

it('creates an editor', function () {
    $this->artisan('user:create', [
        'email' => 'editor@webis.ro',
        '--role' => 'editor',
        '--password' => 'secret-password-123',
    ])->assertSuccessful();

    expect(User::sole()->role)->toBe(UserRole::Editor)
        ->and(User::sole()->name)->toBe('editor');
});

it('generates a password when not interactive', function () {
    $this->artisan('user:create', ['email' => 'ops@webis.ro', '--no-interaction' => true])
        ->expectsOutputToContain('Generated password')
        ->assertSuccessful();

    expect(User::where('email', 'ops@webis.ro')->exists())->toBeTrue();
});

it('rejects an unknown role', function () {
    $this->artisan('user:create', ['email' => 'x@webis.ro', '--role' => 'owner', '--password' => 'secret-password-123'])
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@webis.ro']);

    $this->artisan('user:create', ['email' => 'taken@webis.ro', '--password' => 'secret-password-123'])
        ->assertFailed();

    expect(User::count())->toBe(1);
});
