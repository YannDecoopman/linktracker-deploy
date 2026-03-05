<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indexation_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')
                ->constrained('indexation_campaigns')
                ->cascadeOnDelete();
            $table->foreignId('backlink_id')
                ->nullable()
                ->constrained('backlinks')
                ->nullOnDelete();
            $table->string('source_url', 2048);
            $table->string('submission_status', 30)->default('pending');
            $table->json('provider_response')->nullable();
            $table->string('provider_task_id', 255)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('check_24h_at')->nullable();
            $table->timestamp('check_48h_at')->nullable();
            $table->timestamp('check_7d_at')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->boolean('last_check_result')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('campaign_id');
            $table->index('backlink_id');
            $table->index('submission_status');
            $table->index('submitted_at');
            $table->index('last_checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indexation_submissions');
    }
};
