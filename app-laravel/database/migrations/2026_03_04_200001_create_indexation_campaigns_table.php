<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indexation_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('status', 30)->default('pending');
            $table->string('name', 255)->nullable();
            $table->unsignedInteger('total_urls')->default(0);
            $table->unsignedInteger('submitted_count')->default(0);
            $table->unsignedInteger('indexed_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('provider');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indexation_campaigns');
    }
};
