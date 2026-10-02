<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slug_history', function (Blueprint $table) {
            $table->id();

            // Polymorphic: a renamed page, project, post or category.
            // `sluggable_type` stores the morph alias, `sluggable_id` the current row.
            $table->morphs('sluggable');

            // The slug that used to point at this record. When a public URL hits
            // an unknown slug, we look it up here and 301 to the record's current
            // slug — earned rankings survive renames instead of 404ing.
            $table->string('old_slug');

            $table->timestamps();

            // One old slug can only map to one record per model type.
            $table->unique(['sluggable_type', 'old_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_history');
    }
};
