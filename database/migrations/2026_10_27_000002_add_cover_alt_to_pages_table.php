<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An article's cover: the image itself is a media-library file on the page
 * (its `cover` collection, Page::COVER); this is what it shows, in
 * Romanian, for its alt text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('cover_alt', 125)->nullable()->after('hero_asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('cover_alt');
        });
    }
};
