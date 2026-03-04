<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('indexation_provider', 50)->default('speedyindex')->after('dataforseo_password_encrypted');
            $table->text('speedyindex_api_key_encrypted')->nullable()->after('indexation_provider');
            $table->text('omegaindexer_api_key_encrypted')->nullable()->after('speedyindex_api_key_encrypted');
            $table->text('rocketindexer_api_key_encrypted')->nullable()->after('omegaindexer_api_key_encrypted');
            $table->text('ralfyindex_api_key_encrypted')->nullable()->after('rocketindexer_api_key_encrypted');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'indexation_provider',
                'speedyindex_api_key_encrypted',
                'omegaindexer_api_key_encrypted',
                'rocketindexer_api_key_encrypted',
                'ralfyindex_api_key_encrypted',
            ]);
        });
    }
};
