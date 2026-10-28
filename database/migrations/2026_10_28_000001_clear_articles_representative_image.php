<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * An article's "Imagine reprezentativă": the old WordPress post's featured
 * image, brought over by the import. An article has its cover for every
 * place that image stood for (cards, the share image), so it was never
 * shown, and the field is gone from an article's form. Cleared, with its
 * usage rows, so the media library does not count them; the images stay
 * in the library.
 */
return new class extends Migration
{
    public function up(): void
    {
        $articles = DB::table('pages')->where('type', 'article')->whereNotNull('hero_asset_id');

        DB::table('asset_usages')
            ->where('usable_type', 'page')
            ->where('field', 'hero')
            ->whereIn('usable_id', (clone $articles)->select('id'))
            ->delete();

        $articles->update(['hero_asset_id' => null]);
    }

    public function down(): void
    {
        // The images are not put back: nothing showed them.
    }
};
