<?php

namespace App\Providers;

use App\Blocks\BlockRegistry;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\Brand;
use App\Support\RichText\RichText;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(RichText::class);
        $this->app->scoped(Brand::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Lazy loading, silently discarded attributes and missing attributes
        // throw outside production, so N+1s surface in dev and tests.
        Model::shouldBeStrict(! app()->isProduction());

        // Polymorphic columns (slug_history, revisions, asset_usages, media) store
        // these short aliases, never class names — so a model can be renamed or
        // moved without rewriting rows. Enforced: an unmapped model throws.
        Relation::enforceMorphMap([
            'user' => User::class,
            'page' => Page::class,
            'project' => Project::class,
            'project_category' => ProjectCategory::class,
            'post' => Post::class,
            'post_category' => PostCategory::class,
            'client' => Client::class,
            'testimonial' => Testimonial::class,
            'asset' => Asset::class,
            'lead' => Lead::class,
        ]);

        // Users, settings, redirects and the 404 log. Editors run everything else.
        Gate::define('admin-only', fn (User $user): bool => $user->isAdmin());

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
