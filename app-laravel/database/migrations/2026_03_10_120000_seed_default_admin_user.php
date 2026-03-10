<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->count() === 0) {
            DB::table('users')->insert([
                'name' => 'Admin',
                'email' => 'admin@linktracker.dev',
                'password' => Hash::make('changeme'),
                'email_verified_at' => now(),
                'check_frequency' => 'daily',
                'http_timeout' => 30,
                'email_alerts_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Don't delete users on rollback
    }
};
