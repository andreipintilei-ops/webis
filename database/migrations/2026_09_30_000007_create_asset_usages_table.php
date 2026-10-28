<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Where each asset is used, rebuilt whenever the referencing model is
        // saved (App\Concerns\TracksAssetUsage). Powers the "used in" list and
        // blocks deleting an image that is still referenced.
        Schema::create('asset_usages', function (Blueprint $table) {
            $table->id();

            // Asset::deleting refuses while usages exist, so this cascade never
            // removes a live reference.
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();

            $table->morphs('usable');

            // Which field holds the reference: cover, hero, blocks, seo.og_image...
            $table->string('field', 50);

            $table->timestamps();

            $table->unique(['asset_id', 'usable_type', 'usable_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_usages');
    }
};
