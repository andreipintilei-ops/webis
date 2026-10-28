<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per uploaded file. The file itself (and its WebP conversions)
        // lives in spatie's `media` table, attached to this asset. Content refers
        // to images by `asset_id` only, so alt text and replacements are edited in
        // one place and show up everywhere the image is used.
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();

            // Alt text lives on the asset, not on each usage. Empty means the image
            // is decorative (alt="").
            $table->string('alt')->nullable();
            $table->string('caption', 500)->nullable();

            // Intrinsic dimensions, copied from the original on upload. Every <img>
            // gets width/height so the page never shifts while images load (CLS).
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
