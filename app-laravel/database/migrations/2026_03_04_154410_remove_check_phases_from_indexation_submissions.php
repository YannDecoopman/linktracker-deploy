<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('indexation_submissions', function (Blueprint $table) {
            $table->dropColumn(['check_24h_at', 'check_48h_at']);
        });
    }

    public function down(): void
    {
        Schema::table('indexation_submissions', function (Blueprint $table) {
            $table->timestamp('check_24h_at')->nullable()->after('submitted_at');
            $table->timestamp('check_48h_at')->nullable()->after('check_24h_at');
        });
    }
};
