<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // admin | editor. There is no public registration — every account
            // is staff, created with `php artisan user:create`. Hand-rolled
            // RBAC (no package), same as Mydentist.
            $table->enum('role', ['admin', 'editor'])
                ->default('editor')
                ->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
