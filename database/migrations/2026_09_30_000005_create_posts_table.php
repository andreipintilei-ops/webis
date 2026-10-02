<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // /blog/categorie/{slug}
            $table->string('slug')->unique();

            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('seo')->nullable();
            $table->timestamps();
        });

        // Blog posts, served at /blog/{slug}.
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();

            // Tiptap JSON is the source of truth; `body_html` is rendered from it on
            // save (allowed node types only) so public pages never parse JSON.
            $table->json('body')->nullable();
            $table->longText('body_html')->nullable();

            // Table of contents built from the body's headings: [{id, text, level}].
            $table->json('toc')->nullable();
            $table->unsignedSmallInteger('reading_minutes')->default(0);

            $table->foreignId('cover_asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('post_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            // The service page the post's closing call-to-action points to.
            $table->foreignId('cta_page_id')->nullable()->constrained('pages')->nullOnDelete();

            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('seo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_categories');
    }
};
