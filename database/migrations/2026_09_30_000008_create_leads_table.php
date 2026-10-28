<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quote requests from the public form.
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('company')->nullable();

            // The service the visitor picked. The label is a snapshot so the lead
            // still reads correctly after the page is renamed or deleted.
            $table->foreignId('service_page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('service_label')->nullable();

            $table->string('budget', 100)->nullable();
            $table->text('message')->nullable();

            // Attribution: the page the form was sent from, the first external
            // referrer, UTM parameters and the Google Ads click id.
            $table->string('source_url', 2048)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->json('utm')->nullable();
            $table->string('gclid')->nullable();

            // GDPR: when the visitor ticked consent. IP and user agent are kept for
            // spam investigation only and cleared after 90 days.
            $table->timestamp('consent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();

            // new, contacted, offer_sent, won, lost, spam (App\Enums\LeadStatus).
            $table->string('status', 20)->default('new');
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
