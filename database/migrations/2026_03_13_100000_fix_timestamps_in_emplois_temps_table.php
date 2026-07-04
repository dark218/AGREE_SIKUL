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
        // idempotence guard
        if (Schema::hasTable('emplois_temps')) if (Schema::hasTable('emplois_temps')) Schema::table('emplois_temps', function (Blueprint $table) {
            // Convert TIMESTAMP to DATETIME to avoid auto-update conflicts
            $table->dateTime('created_at')->nullable()->change();
            $table->dateTime('updated_at')->nullable()->change();
            $table->dateTime('deleted_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('emplois_temps')) if (Schema::hasTable('emplois_temps')) Schema::table('emplois_temps', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
            $table->timestamp('deleted_at')->nullable()->change();
        });
    }
};
