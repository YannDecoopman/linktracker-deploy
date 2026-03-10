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
        Schema::table('backlinks', function (Blueprint $table) {
            // Tier & Type
            $table->enum('tier_level', ['tier1', 'tier2'])->default('tier1')->after('project_id');
            $table->foreignId('parent_backlink_id')->nullable()->constrained('backlinks')->onDelete('set null')->after('tier_level');
            $table->enum('spot_type', ['external', 'internal'])->default('external')->after('parent_backlink_id');

            // Dates
            $table->date('published_at')->nullable()->after('last_checked_at');
            $table->date('expires_at')->nullable()->after('published_at');

            // Financier
            $table->decimal('price', 10, 2)->nullable()->after('expires_at');
            $table->string('currency', 3)->nullable()->after('price')->comment('EUR, USD, GBP, CAD, BRL, MXN, ARS, COP, CLP, PEN');
            $table->boolean('invoice_paid')->default(false)->after('currency');

            // Plateforme & Contact
            $table->foreignId('platform_id')->nullable()->constrained('platforms')->onDelete('set null')->after('invoice_paid');
            $table->text('contact_info')->nullable()->after('platform_id')->comment('Contact info when platform is "Other"');

            // Tracking
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null')->after('contact_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backlinks', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropForeign(['platform_id']);
            $table->dropForeign(['parent_backlink_id']);

            $table->dropColumn([
                'tier_level',
                'parent_backlink_id',
                'spot_type',
                'published_at',
                'expires_at',
                'price',
                'currency',
                'invoice_paid',
                'platform_id',
                'contact_info',
                'created_by_user_id',
            ]);
        });
    }
};
