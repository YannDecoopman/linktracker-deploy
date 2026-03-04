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
        Schema::table('backlink_snapshots', function (Blueprint $table) {
            $table->unsignedInteger('count_perfect')->default(0)->after('count_total');
            $table->unsignedInteger('count_not_indexed')->default(0)->after('count_perfect');
            $table->unsignedInteger('count_nofollow')->default(0)->after('count_not_indexed');
        });
    }

    public function down(): void
    {
        Schema::table('backlink_snapshots', function (Blueprint $table) {
            $table->dropColumn(['count_perfect', 'count_not_indexed', 'count_nofollow']);
        });
    }
};
