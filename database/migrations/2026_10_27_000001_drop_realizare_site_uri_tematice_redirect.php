<?php

use App\Support\Seo\Sitemap;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The industry index moved from /servicii/ to /realizare-site-uri-tematice/
 * (routes/web.php), so the legacy import's 301 from there to /servicii/
 * goes: it would send the index's own address back to its old one, and the
 * sitemap leaves out any address the table redirects.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('redirects')
            ->whereIn('source_path', ['/realizare-site-uri-tematice', '/realizare-site-uri-tematice/'])
            ->whereIn('target', ['/servicii', '/servicii/'])
            ->delete();

        Sitemap::forget();
    }

    public function down(): void
    {
        // The row pointed at an address that is itself a redirect now:
        // nothing to put back.
    }
};
