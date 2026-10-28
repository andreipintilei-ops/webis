<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The portfolio (/portofoliu, /proiect/{slug}): what each project was
 * (App\Enums\ProjectType — the page's filter), what the client needed, what
 * was built and with what, and its images. And which of Webis's products a
 * client uses (App\Enums\Product), for a product page's "Folosit de" strip.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('type', 20)->nullable()->after('client_id');
            $table->text('need')->nullable()->after('summary');
            $table->text('built')->nullable()->after('need');

            // The technologies, as a list of names.
            $table->json('stack')->nullable()->after('built');

            // Asset ids, in order: the gallery on the project's page.
            $table->json('images')->nullable()->after('cover_asset_id');

            $table->index(['type', 'status']);
        });

        Schema::table('clients', function (Blueprint $table) {
            // Product values (smilesoft, rims…) the client uses.
            $table->json('products')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('products');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['type', 'status']);
            $table->dropColumn(['type', 'need', 'built', 'stack', 'images']);
        });
    }
};
