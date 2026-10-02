<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Portfolio items, served at /clienti/{slug}.
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year')->nullable();

            // The live site, when it still exists.
            $table->string('url')->nullable();

            $table->text('summary')->nullable();
            $table->foreignId('cover_asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->json('blocks')->nullable();

            // Results worth showing, as [{label, value}], e.g. "+180% comenzi".
            $table->json('metrics')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('seo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['is_featured', 'sort_order']);
        });

        Schema::create('project_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // /clienti/categorie/{slug}
            $table->string('slug')->unique();

            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('seo')->nullable();
            $table->timestamps();
        });

        Schema::create('project_project_category', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_category_id')->constrained()->cascadeOnDelete();

            $table->primary(['project_id', 'project_category_id']);
            $table->index('project_category_id');
        });

        // Projects shown on a service or industry page ("proiecte relevante").
        // Industry pages need real related work to not be thin content.
        Schema::create('page_project', function (Blueprint $table) {
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['page_id', 'project_id']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_project');
        Schema::dropIfExists('project_project_category');
        Schema::dropIfExists('project_categories');
        Schema::dropIfExists('projects');
    }
};
