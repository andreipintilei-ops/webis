<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Trash, restore and permanent delete for content. The using
 * controller names its route prefix (`admin.{prefix}.index|edit`) and its
 * messages — Romanian participles agree with the noun's gender.
 */
trait ManagesTrash
{
    abstract protected function routePrefix(): string;

    /**
     * @return array{trashed: string, restored: string, purged: string}
     */
    abstract protected function trashMessages(): array;

    protected function trash(Page|Project|Post $content): RedirectResponse
    {
        $content->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => $this->trashMessages()['trashed']]);

        return to_route("admin.{$this->routePrefix()}.index");
    }

    protected function untrash(Page|Project|Post $content): RedirectResponse
    {
        $content->restore();
        Inertia::flash('toast', ['type' => 'success', 'message' => $this->trashMessages()['restored']]);

        return to_route("admin.{$this->routePrefix()}.edit", $content->getKey());
    }

    /**
     * Frees the URL; old links then 404 unless a redirect is added.
     */
    protected function purge(Page|Project|Post $content): RedirectResponse
    {
        $content->forceDelete();
        Inertia::flash('toast', ['type' => 'success', 'message' => $this->trashMessages()['purged']]);

        return to_route("admin.{$this->routePrefix()}.index", ['trashed' => 1]);
    }
}
