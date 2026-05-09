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
        if (Schema::hasTable('emplois_temps')) if (Schema::hasTable('emplois_temps')) Schema::table('emplois_temps', function (Blueprint $table) {
            // Change date columns to datetime to preserve time
            $table->dateTime('date_debut')->change();
            $table->dateTime('date_fin')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('emplois_temps')) if (Schema::hasTable('emplois_temps')) Schema::table('emplois_temps', function (Blueprint $table) {
            $table->date('date_debut')->change();
            $table->date('date_fin')->change();
        });
    }
};
