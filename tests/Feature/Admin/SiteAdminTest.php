<?php

use App\Enums\LeadStatus;
use App\Enums\RedirectCode;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Lead;
use App\Models\NotFoundLog;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Testimonial;
use App\Models\User;
use App\Settings\AnalyticsSettings;
use App\Settings\CompanySettings;
use App\Settings\LeadSettings;
use App\Settings\SeoSettings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

// ---- Access ---------------------------------------------------------------

it('keeps editors out of the admin-only areas', function (string $route) {
    $this->actingAs(User::factory()->create())
        ->get(route($route))
        ->assertForbidden();
})->with(['admin.redirects.index', 'admin.not-found.index', 'admin.settings.edit', 'admin.users.index']);

it('lets editors work on clients, testimonials and leads', function (string $route) {
    $this->actingAs(User::factory()->create())
        ->get(route($route))
        ->assertOk();
})->with(['admin.clients.index', 'admin.testimonials.index', 'admin.leads.index']);

// ---- Dashboard --------------------------------------------------------------

it('shows what needs attention on the dashboard', function () {
    Lead::factory()->count(2)->create();
    Page::factory()->draft()->create();
    Page::factory()->scheduled()->create(['title' => 'Oferta de Crăciun']);
    NotFoundLog::factory()->create(['path' => '/pagina-veche', 'hits' => 40]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.newLeads', 2)
            ->where('stats.drafts', 1)
            ->where('scheduled.0.title', 'Oferta de Crăciun')
            ->where('topNotFound.0.path', '/pagina-veche'));
});

it('hides the 404 list from editors on the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('topNotFound', null));
});

// ---- Clients and testimonials ---------------------------------------------

it('adds, edits, orders and trashes clients', function () {
    $this->actingAs(User::factory()->create());
    $logo = Asset::factory()->create();

    $this->post(route('admin.clients.store'), [
        'name' => 'Primăria Iași',
        'logo_asset_id' => $logo->id,
        'is_institution' => true,
        'show_in_logos' => true,
    ])->assertSessionHasNoErrors();

    $client = Client::sole();
    expect($client->is_institution)->toBeTrue()->and($logo->usages()->count())->toBe(1);

    $this->put(route('admin.clients.update', $client), ['name' => 'Primăria Municipiului Iași', 'show_in_logos' => false])
        ->assertSessionHasNoErrors();
    expect($client->fresh()?->show_in_logos)->toBeFalse();

    $other = Client::factory()->create();
    $this->post(route('admin.clients.reorder'), ['ids' => [$other->id, $client->id]]);
    expect($other->fresh()?->sort_order)->toBe(0)->and($client->fresh()?->sort_order)->toBe(1);

    $this->delete(route('admin.clients.destroy', $client))->assertRedirect();
    expect($client->fresh()?->trashed())->toBeTrue();

    $this->post(route('admin.clients.restore', $client->id))->assertRedirect();
    expect($client->fresh()?->trashed())->toBeFalse();
});

it('opens the panel for the client linked from the media library', function () {
    $client = Client::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.clients.index', ['edit' => $client->id]))
        ->assertInertia(fn (Assert $page) => $page->where('editId', $client->id));
});

it('validates testimonial ratings', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.testimonials.store'), ['author_name' => 'Ana', 'quote' => 'Super', 'rating' => 7])
        ->assertSessionHasErrors('rating');

    $this->post(route('admin.testimonials.store'), ['author_name' => 'Ana', 'quote' => 'Super', 'rating' => 5, 'is_visible' => true])
        ->assertSessionHasNoErrors();

    expect(Testimonial::sole()->rating)->toBe(5);
});

// ---- Leads ------------------------------------------------------------------

it('hides spam from the inbox unless asked for', function () {
    $this->actingAs(User::factory()->create());
    Lead::factory()->create(['name' => 'Ana']);
    Lead::factory()->spam()->create(['name' => 'Bot']);

    $this->get(route('admin.leads.index'))
        ->assertInertia(fn (Assert $page) => $page->has('leads.data', 1)->where('leads.data.0.name', 'Ana'));

    $this->get(route('admin.leads.index', ['status' => 'spam']))
        ->assertInertia(fn (Assert $page) => $page->has('leads.data', 1)->where('leads.data.0.name', 'Bot'));
});

it('moves a lead through the pipeline', function () {
    $user = User::factory()->create();
    $lead = Lead::factory()->create();

    $this->actingAs($user)
        ->patch(route('admin.leads.update', $lead), [
            'status' => 'contacted',
            'notes' => 'Sunat, revine mâine.',
            'assigned_to' => $user->id,
        ])->assertSessionHasNoErrors();

    expect($lead->fresh()?->status)->toBe(LeadStatus::Contacted)
        ->and($lead->fresh()?->assigned_to)->toBe($user->id);
});

it('exports leads as an Excel-friendly CSV', function () {
    $this->actingAs(User::factory()->create());
    Lead::factory()->create(['name' => 'Ștefan Țurcanu']);

    $csv = $this->get(route('admin.leads.export'))->assertOk()->streamedContent();

    expect($csv)->toStartWith("\xEF\xBB\xBF")
        ->toContain('Data;Nume;Email')
        ->toContain('Ștefan Țurcanu');
});

// ---- Redirects --------------------------------------------------------------

describe('redirects', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->admin()->create());
    });

    it('stores sources in their canonical form', function () {
        $this->post(route('admin.redirects.store'), [
            'source_path' => 'https://www.webis.ro/Proiect/Vechi/',
            'match_type' => 'exact',
            'status_code' => 301,
            'target' => '/portofoliu/nou',
        ])->assertSessionHasNoErrors();

        expect(Redirect::sole()->source_path)->toBe('/proiect/vechi');
    });

    it('refuses duplicates, loops and chains', function () {
        Redirect::factory()->create(['source_path' => '/a', 'target' => '/b']);

        $this->post(route('admin.redirects.store'), ['source_path' => '/A/', 'match_type' => 'exact', 'status_code' => 301, 'target' => '/c'])
            ->assertSessionHasErrors('source_path');
        $this->post(route('admin.redirects.store'), ['source_path' => '/x', 'match_type' => 'exact', 'status_code' => 301, 'target' => '/x/'])
            ->assertSessionHasErrors('target');
        $this->post(route('admin.redirects.store'), ['source_path' => '/z', 'match_type' => 'exact', 'status_code' => 301, 'target' => '/a'])
            ->assertSessionHasErrors('target');
    });

    it('needs no target for 410 Gone', function () {
        $this->post(route('admin.redirects.store'), ['source_path' => '/wp-login.php', 'match_type' => 'exact', 'status_code' => 410, 'target' => '/ignorat'])
            ->assertSessionHasNoErrors();

        expect(Redirect::sole()->status_code)->toBe(RedirectCode::Gone)
            ->and(Redirect::sole()->target)->toBeNull();
    });

    it('imports a CSV, updating existing sources and skipping bad rows', function () {
        Redirect::factory()->create(['source_path' => '/proiect/a', 'target' => '/vechi']);

        $csv = "sursa;destinatie;cod\n/proiect/a/;/portofoliu/a\n/proiect/b;/portofoliu/b;302\n/category/x;;410\n/rau;javascript:alert(1)\n";

        $this->post(route('admin.redirects.import'), [
            'file' => UploadedFile::fake()->createWithContent('redirects.csv', $csv),
        ])->assertRedirect();

        expect(Redirect::query()->pluck('target', 'source_path')->all())->toEqual([
            '/proiect/a' => '/portofoliu/a',
            '/proiect/b' => '/portofoliu/b',
            '/category/x' => null,
        ])->and(Redirect::where('source_path', '/proiect/b')->sole()->status_code)->toBe(RedirectCode::Temporary);
    });

    it('prefills a redirect from the 404 log', function () {
        $this->get(route('admin.redirects.index', ['source' => '/Pagina-Veche/']))
            ->assertInertia(fn (Assert $page) => $page->where('prefillSource', '/pagina-veche'));
    });
});

// ---- 404 log ----------------------------------------------------------------

it('ignores 404s and marks the ones already redirected', function () {
    $this->actingAs(User::factory()->admin()->create());
    $log = NotFoundLog::factory()->create(['path' => '/vechi']);
    NotFoundLog::factory()->create(['path' => '/xmlrpc.php']);
    Redirect::factory()->create(['source_path' => '/vechi']);

    $this->get(route('admin.not-found.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 2)
            ->where('logs.data', fn ($rows) => collect($rows)->firstWhere('path', '/vechi')['has_redirect'] === true));

    $noise = NotFoundLog::where('path', '/xmlrpc.php')->sole();
    $this->patch(route('admin.not-found.update', $noise), ['is_ignored' => true])->assertRedirect();

    $this->get(route('admin.not-found.index'))->assertInertia(fn (Assert $page) => $page->has('logs.data', 1));

    $this->delete(route('admin.not-found.clear-ignored'))->assertRedirect();
    expect(NotFoundLog::count())->toBe(1)->and($log->fresh())->not->toBeNull();
});

// ---- Settings ---------------------------------------------------------------

it('saves each settings group on its own', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->put(route('admin.settings.company'), [
        'name' => 'Webis',
        'legal_name' => 'Webis SRL',
        'phone' => '+40 700 000 000',
        'locality' => 'Iași',
        'region' => 'Iași',
        'country_code' => 'ro',
        'opening_hours' => [['days' => ['Mo', 'Tu', 'We', 'Th', 'Fr'], 'opens' => '09:00', 'closes' => '18:00']],
        'social' => ['facebook' => 'https://facebook.com/webis', 'myspace' => 'https://myspace.com/x', 'instagram' => null],
    ])->assertSessionHasNoErrors();

    $company = app(CompanySettings::class);
    expect($company->phone)->toBe('+40 700 000 000')
        ->and($company->country_code)->toBe('RO')
        ->and($company->social)->toBe(['facebook' => 'https://facebook.com/webis']);

    $this->put(route('admin.settings.analytics'), ['ga4_measurement_id' => 'UA-123'])
        ->assertSessionHasErrors('ga4_measurement_id');
    $this->put(route('admin.settings.analytics'), ['ga4_measurement_id' => 'G-ABC123XYZ'])
        ->assertSessionHasNoErrors();
    expect(app(AnalyticsSettings::class)->ga4_measurement_id)->toBe('G-ABC123XYZ');

    $this->put(route('admin.settings.leads'), [
        'notification_emails' => ['office@webis.ro'],
        'budget_options' => ['sub 5.000 lei', 'peste 5.000 lei'],
        'send_confirmation' => false,
    ])->assertSessionHasNoErrors();
    expect(app(LeadSettings::class)->notification_emails)->toBe(['office@webis.ro'])
        ->and(app(LeadSettings::class)->send_confirmation)->toBeFalse();
});

it('rejects opening hours that close before they open', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.company'), [
            'name' => 'Webis', 'legal_name' => 'Webis SRL', 'locality' => 'Iași', 'region' => 'Iași', 'country_code' => 'RO',
            'opening_hours' => [['days' => ['Mo'], 'opens' => '18:00', 'closes' => '09:00']],
        ])->assertSessionHasErrors('opening_hours.0.closes');
});

// ---- Users ------------------------------------------------------------------

describe('users', function () {
    beforeEach(function () {
        $this->actingAs($this->admin = User::factory()->admin()->create());
    });

    it('creates an account and emails a link to set the password', function () {
        Notification::fake();

        $this->post(route('admin.users.store'), ['name' => 'Ioana', 'email' => 'IOANA@webis.ro', 'role' => 'editor'])
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'ioana@webis.ro')->sole();
        expect($user->role)->toBe(UserRole::Editor);
        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('never leaves the site without an administrator', function () {
        $this->put(route('admin.users.update', $this->admin), ['name' => $this->admin->name, 'role' => 'editor'])
            ->assertSessionHasErrors('role');

        $other = User::factory()->admin()->create();
        $this->put(route('admin.users.update', $this->admin), ['name' => $this->admin->name, 'role' => 'editor'])
            ->assertSessionHasNoErrors();

        expect($other->fresh()?->isAdmin())->toBeTrue();
    });

    it('does not let you delete yourself', function () {
        $this->delete(route('admin.users.destroy', $this->admin))->assertSessionHasErrors('user');

        $editor = User::factory()->create();
        $this->delete(route('admin.users.destroy', $editor))->assertSessionHasNoErrors();
        expect($editor->fresh())->toBeNull();
    });
});

it('allows an empty title suffix', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.seo'), ['title_suffix' => ''])
        ->assertSessionHasNoErrors();

    expect(app(SeoSettings::class)->title_suffix)->toBe('');
});
