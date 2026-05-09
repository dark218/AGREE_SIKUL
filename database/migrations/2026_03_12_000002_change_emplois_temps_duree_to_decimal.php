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
            // Change duree from INT to DECIMAL to support 1.5, 2.5, etc.
            $table->decimal('duree', 5, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('emplois_temps')) if (Schema::hasTable('emplois_temps')) Schema::table('emplois_temps', function (Blueprint $table) {
            $table->integer('duree')->nullable()->change();
        });
    }
};
