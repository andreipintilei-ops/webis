<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A client's type (App\Enums\ClientType: university, institution, company)
 * in place of the "is an institution" flag — /clienti groups them by it.
 * The flag carries over: an institution named as a university becomes one,
 * any other institution stays an institution, the rest are companies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('type', 20)->default('company')->after('url');
        });

        DB::table('clients')->where('is_institution', true)->update(['type' => 'institution']);
        DB::table('clients')
            ->where('is_institution', true)
            ->where(fn ($query) => $query->where('name', 'like', '%Universit%')->orWhere('name', 'like', '%Academi%'))
            ->update(['type' => 'university']);

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('is_institution');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_institution')->default(false)->after('url');
        });

        DB::table('clients')->whereIn('type', ['university', 'institution'])->update(['is_institution' => true]);

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
