<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NotFoundLogController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProjectCategoryController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\RevisionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
| The admin CMS (Inertia + Vue). Every route is staff-only; the users,
| settings, redirects and 404 areas are additionally admin-only. Records are
| bound by id here — slugs are public URLs and editors can change them.
*/
Route::middleware(['auth', 'verified', 'can.access-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::get('media/browse', [MediaController::class, 'browse'])->name('media.browse');
    Route::post('media', [MediaController::class, 'store'])->name('media.store');
    Route::get('media/{asset}', [MediaController::class, 'show'])->name('media.show');
    Route::patch('media/{asset}', [MediaController::class, 'update'])->name('media.update');
    Route::post('media/{asset}/inlocuieste', [MediaController::class, 'replace'])->name('media.replace');
    Route::delete('media/{asset}', [MediaController::class, 'destroy'])->name('media.destroy');

    Route::controller(PageController::class)->prefix('pagini')->name('pages.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('creeaza', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('ordine', 'reorder')->name('reorder');
        Route::get('{page:id}/editeaza', 'edit')->name('edit');
        Route::put('{page:id}', 'update')->name('update');
        Route::delete('{page:id}', 'destroy')->name('destroy');
        Route::post('{page:id}/restaureaza', 'restore')->name('restore')->withTrashed();
        Route::delete('{page:id}/definitiv', 'forceDestroy')->name('force-destroy')->withTrashed();
    });

    Route::controller(ProjectController::class)->prefix('portofoliu')->name('projects.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('creeaza', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('ordine', 'reorder')->name('reorder');
        Route::get('{project:id}/editeaza', 'edit')->name('edit');
        Route::put('{project:id}', 'update')->name('update');
        Route::delete('{project:id}', 'destroy')->name('destroy');
        Route::post('{project:id}/restaureaza', 'restore')->name('restore')->withTrashed();
        Route::delete('{project:id}/definitiv', 'forceDestroy')->name('force-destroy')->withTrashed();
    });

    Route::controller(PostController::class)->prefix('blog')->name('posts.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('creeaza', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{post:id}/editeaza', 'edit')->name('edit');
        Route::put('{post:id}', 'update')->name('update');
        Route::delete('{post:id}', 'destroy')->name('destroy');
        Route::post('{post:id}/restaureaza', 'restore')->name('restore')->withTrashed();
        Route::delete('{post:id}/definitiv', 'forceDestroy')->name('force-destroy')->withTrashed();
    });

    foreach ([
        'project-categories' => ['portofoliu/categorii', ProjectCategoryController::class],
        'post-categories' => ['blog/categorii', PostCategoryController::class],
    ] as $name => [$uri, $controller]) {
        Route::controller($controller)->prefix($uri)->name("{$name}.")->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('{category}', 'update')->name('update')->whereNumber('category');
            Route::delete('{category}', 'destroy')->name('destroy')->whereNumber('category');
        });
    }

    Route::post('revizii/{revision}/restaureaza', [RevisionController::class, 'restore'])->name('revisions.restore');

    // Small lists edited in a side panel.
    foreach ([
        'clients' => ['clienti', ClientController::class, 'client'],
        'testimonials' => ['testimoniale', TestimonialController::class, 'testimonial'],
    ] as $name => [$uri, $controller, $param]) {
        Route::controller($controller)->prefix($uri)->name("{$name}.")->group(function () use ($param) {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::post('ordine', 'reorder')->name('reorder');
            Route::put("{{$param}}", 'update')->name('update');
            Route::delete("{{$param}}", 'destroy')->name('destroy');
            Route::post("{{$param}}/restaureaza", 'restore')->name('restore')->withTrashed();
        });
    }

    Route::controller(LeadController::class)->prefix('cereri')->name('leads.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('export', 'export')->name('export');
        Route::get('{lead}', 'show')->name('show');
        Route::patch('{lead}', 'update')->name('update');
        Route::delete('{lead}', 'destroy')->name('destroy');
    });

    // Site-wide areas: administrators only.
    Route::middleware('can:admin-only')->group(function () {
        Route::controller(RedirectController::class)->prefix('redirectionari')->name('redirects.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::post('import', 'import')->name('import');
            Route::get('export', 'export')->name('export');
            Route::put('{redirect}', 'update')->name('update');
            Route::delete('{redirect}', 'destroy')->name('destroy');
        });

        Route::controller(NotFoundLogController::class)->prefix('erori-404')->name('not-found.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::delete('/', 'clearIgnored')->name('clear-ignored');
            Route::patch('{log}', 'update')->name('update');
            Route::delete('{log}', 'destroy')->name('destroy');
        });

        Route::controller(SettingsController::class)->prefix('setari')->name('settings.')->group(function () {
            Route::get('/', 'edit')->name('edit');
            Route::put('identitate', 'updateBrand')->name('brand');
            Route::put('companie', 'updateCompany')->name('company');
            Route::put('seo', 'updateSeo')->name('seo');
            Route::put('analiza', 'updateAnalytics')->name('analytics');
            Route::put('cereri', 'updateLeads')->name('leads');
        });

        Route::controller(UserController::class)->prefix('utilizatori')->name('users.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('{user}', 'update')->name('update');
            Route::delete('{user}', 'destroy')->name('destroy');
            Route::post('{user}/resetare-parola', 'sendReset')->name('send-reset');
        });
    });
});
