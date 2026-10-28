<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesCategories;
use App\Http\Controllers\Controller;
use App\Models\ProjectCategory;

class ProjectCategoryController extends Controller
{
    use ManagesCategories;

    protected function model(): string
    {
        return ProjectCategory::class;
    }

    protected function config(): array
    {
        return [
            'title' => 'Categorii portofoliu',
            'itemsLabel' => 'proiecte',
            'relation' => 'projects',
            'routePrefix' => 'project-categories',
        ];
    }
}
