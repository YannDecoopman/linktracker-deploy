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
        // Remet is_indexed = null pour les backlinks ayant is_indexed = false
        // ET ayant été vérifiés (last_checked_at not null) sans noindex détecté.
        // Ces false provenaient du CSV d'import et n'ont pas été invalidés par le checker.
        // Sans API externe, seul un noindex HTML peut confirmer false.
        \DB::table('backlinks')
            ->where('is_indexed', false)
            ->whereNotNull('last_checked_at')
            ->update(['is_indexed' => null]);
    }

    public function down(): void
    {
        // Non réversible.
    }
};
