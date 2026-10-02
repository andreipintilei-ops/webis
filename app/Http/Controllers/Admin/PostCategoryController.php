<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesCategories;
use App\Http\Controllers\Controller;
use App\Models\PostCategory;

class PostCategoryController extends Controller
{
    use ManagesCategories;

    protected function model(): string
    {
        return PostCategory::class;
    }

    protected function config(): array
    {
        return [
            'title' => 'Categorii blog',
            'itemsLabel' => 'articole',
            'relation' => 'posts',
            'routePrefix' => 'post-categories',
        ];
    }
}
