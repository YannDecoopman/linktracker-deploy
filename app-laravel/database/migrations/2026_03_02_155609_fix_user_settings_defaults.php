<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('check_frequency')->update([
            'check_frequency'      => 'daily',
            'http_timeout'         => 30,
            'email_alerts_enabled' => 1,
            'seo_provider'         => 'custom',
        ]);
    }

    public function down(): void {}
};
