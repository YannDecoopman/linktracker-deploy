<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('dataforseo_login_encrypted')->nullable()->after('seo_api_key_encrypted');
            $table->text('dataforseo_password_encrypted')->nullable()->after('dataforseo_login_encrypted');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dataforseo_login_encrypted', 'dataforseo_password_encrypted']);
        });
    }
};
