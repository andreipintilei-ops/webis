<?php

use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\ClientsController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\ProjectController;
use App\Http\Controllers\Public\SoftwareController;
use App\Support\ReservedSlugs;
use Illuminate\Support\Facades\Route;

/*
| Public, server-rendered (Blade) pages. Built up in Phase 3.
*/
Route::get('/', HomeController::class)->name('home');

// Section pages. Their content comes with the design.
Route::view('solutii', 'public.services.index')->name('services.index');
// The client work: the old site's addresses, which already rank — the list
// at /portofoliu (the menu calls it Clienți), each project at /proiect/{slug}.
Route::get('portofoliu', ClientsController::class)->name('projects.index');
Route::permanentRedirect('clienti', '/portofoliu');
// The old site's archive of its projects.
Route::permanentRedirect('proiect', '/portofoliu');
Route::get('proiect/{slug}', [ProjectController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('projects.show');
// A design trial kept for later: the services as one pinned story (HomeController).
Route::get('poveste', [HomeController::class, 'poveste'])->name('trials.story');
// The blog: its articles are CMS pages (PageType::Article) at their own root
// addresses, as on the old site; these list them, all or one category's.
Route::get('blog', [ArticleController::class, 'index'])->name('posts.index');
Route::get('blog/categorie/{slug}', [ArticleController::class, 'category'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('posts.category');
Route::view('web', 'public.web')->name('services.web');
// Software la comandă: its canonical address ends in a slash
// (/software-la-comanda/); its own copy, and its software projects.
Route::get('software-la-comanda', SoftwareController::class)->name('services.software');
// Produse SaaS (/produse/): Webis's own products, its own copy. It replaces
// the CMS page of that name, which no longer shows.
Route::view('produse', 'public.products')->name('services.products');

// The contact form (components/site/contact). At a reserved slug, so no CMS
// page can take it.
Route::post('cerere-oferta', [ContactController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('contact.store');

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';

/*
| CMS pages at the site root (/contact, /creare-magazin-online…). Registered
| last, and limited to slug-shaped segments that aren't reserved, so it can
| never shadow an application route.
*/
Route::get('{slug}', [PageController::class, 'show'])
    ->where('slug', '(?!(?:'.implode('|', array_map('preg_quote', ReservedSlugs::ALL)).')$)[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('pages.show');
