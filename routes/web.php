<?php

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Support\ReservedSlugs;
use Illuminate\Support\Facades\Route;

/*
| Public, server-rendered (Blade) pages. Built up in Phase 3.
*/
Route::get('/', HomeController::class)->name('home');

// Section pages. Their content comes with the design.
Route::view('solutii', 'public.services.index')->name('services.index');
Route::view('clienti', 'public.portfolio.index')->name('projects.index');
Route::view('blog', 'public.blog.index')->name('posts.index');

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
