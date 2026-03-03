<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_metrics', function (Blueprint $table) {
            $table->unsignedInteger('referring_domains_count')->nullable()->after('spam_score');
            $table->unsignedInteger('organic_keywords_count')->nullable()->after('referring_domains_count');
        });
    }

    public function down(): void
    {
        Schema::table('domain_metrics', function (Blueprint $table) {
            $table->dropColumn(['referring_domains_count', 'organic_keywords_count']);
        });
    }
};
