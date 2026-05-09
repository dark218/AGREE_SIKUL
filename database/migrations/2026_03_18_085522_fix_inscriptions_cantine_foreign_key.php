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
        if (Schema::hasTable('inscriptions_cantine')) if (Schema::hasTable('inscriptions_cantine')) Schema::table('inscriptions_cantine', function (Blueprint $table) {
            // Drop the incorrect foreign key
            $table->dropForeign(['service_cantine_id']);

            // Recreate with correct table name
            $table->foreign('service_cantine_id')
                ->references('id')
                ->on('services_cantines')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inscriptions_cantine')) if (Schema::hasTable('inscriptions_cantine')) Schema::table('inscriptions_cantine', function (Blueprint $table) {
            // Drop the corrected foreign key
            $table->dropForeign(['service_cantine_id']);

            // Restore the old incorrect one (if needed for rollback)
            $table->foreign('service_cantine_id')
                ->references('id')
                ->on('services_cantine')
                ->cascadeOnDelete();
        });
    }
};
