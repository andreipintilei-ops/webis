<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a client shown in the logos (`show_in_logos`: the logo wall) is
 * also in the hero's moving strip (components/logo-marquee). Every client
 * there so far stays there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('show_in_marquee')->default(true)->after('show_in_logos');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('show_in_marquee');
        });
    }
};
