<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('logo_asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('sector')->nullable();
            $table->string('url')->nullable();

            // Public institutions get their own "notable clients" block.
            $table->boolean('is_institution')->default(false);

            // Whether the logo appears in the client-logos strip.
            $table->boolean('show_in_logos')->default(true);

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['show_in_logos', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
