<?php

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('sends guests to the login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('lets editors and admins into the admin', function (string $state) {
    $user = $state === 'admin' ? User::factory()->admin()->create() : User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->where('auth.isAdmin', $state === 'admin'));
})->with(['editor', 'admin']);

it('keeps the admin-only gate for admins', function () {
    expect(Gate::forUser(User::factory()->admin()->create())->allows('admin-only'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->create())->allows('admin-only'))->toBeFalse();
});

it('shares the count of new leads for the sidebar badge', function () {
    Lead::factory()->count(2)->create();
    Lead::factory()->create(['status' => LeadStatus::Contacted]);
    Lead::factory()->spam()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('newLeads', 2));
});
