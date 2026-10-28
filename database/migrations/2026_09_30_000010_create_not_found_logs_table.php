<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per 404ing path, upserted with a hit count. The admin turns the
        // frequent ones into redirects and ignores the bot noise.
        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path', 512)->unique();
            $table->unsignedInteger('hits')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_referrer', 512)->nullable();
            $table->string('last_user_agent', 512)->nullable();
            $table->boolean('is_ignored')->default(false);
            $table->timestamps();

            $table->index(['is_ignored', 'hits']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_logs');
    }
};
