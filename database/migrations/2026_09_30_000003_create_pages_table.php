<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every CMS page — home, services, industries, legal, contact — in one
        // table. All of them are served at a root slug (/creare-magazin-online),
        // so the old WordPress paths keep working without a prefix.
        Schema::create('pages', function (Blueprint $table) {
            $table->id();

            // home, page, service, industry, legal, contact (App\Enums\PageType).
            $table->string('type', 20);

            $table->string('title');

            // Unique including trashed rows: a slug that once served a page is not
            // reused silently while that page can still be restored.
            $table->string('slug')->unique();

            $table->text('excerpt')->nullable();
            $table->string('icon')->nullable();
            $table->foreignId('hero_asset_id')->nullable()->constrained('assets')->nullOnDelete();

            // Page-builder content: an ordered array of {id, type, v, data}.
            $table->json('blocks')->nullable();

            // Type-specific extras, e.g. price_from and serviceType on services.
            // Not called `attributes`: that name is Eloquent's internal property.
            $table->json('details')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            // draft, scheduled, published (App\Enums\ContentStatus).
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();

            // Per-page overrides: title, description, canonical, noindex, OG image.
            $table->json('seo')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status', 'sort_order']);
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
