<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Manual redirects, mostly old WordPress URLs. Looked up only when a
        // request would otherwise 404, so they never slow down live pages.
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();

            // Normalised path: leading slash, lowercase, no trailing slash, no
            // query string. /proiect/foo, not https://www.webis.ro/proiect/foo/.
            $table->string('source_path', 512)->unique();

            // exact, or prefix (App\Enums\RedirectMatchType).
            $table->string('match_type', 10)->default('exact');

            // A path or absolute URL. Null for 410 Gone.
            $table->string('target', 2048)->nullable();

            // 301, 302 or 410 (App\Enums\RedirectCode).
            $table->unsignedSmallInteger('status_code')->default(301);

            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('match_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
