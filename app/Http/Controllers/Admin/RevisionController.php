<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Revision;
use App\Support\AdminLinks;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RevisionController extends Controller
{
    /**
     * POST /admin/revizii/{revision}/restaureaza — put an older version's
     * content back. The restore is itself saved as a revision, so it can be
     * undone the same way.
     */
    public function restore(Revision $revision): RedirectResponse
    {
        $content = $revision->revisionable;

        abort_unless($content instanceof Page || $content instanceof Project || $content instanceof Post, 404);

        $content->restoreRevision($revision);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Versiunea a fost restaurată.']);

        return redirect((string) AdminLinks::describe($content)['url']);
    }
}
