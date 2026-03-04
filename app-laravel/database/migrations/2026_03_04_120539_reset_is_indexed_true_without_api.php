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
        // Remet à null tous les is_indexed = true posés par l'ancienne logique HTML
        // (avant le fix qui interdisait de mettre true sans API).
        // Les is_indexed = false (noindex détecté) sont conservés car ils sont fiables.
        \DB::table('backlinks')->where('is_indexed', true)->update(['is_indexed' => null]);
    }

    public function down(): void
    {
        // Non réversible : impossible de savoir quelles valeurs true étaient légitimes.
    }
};
