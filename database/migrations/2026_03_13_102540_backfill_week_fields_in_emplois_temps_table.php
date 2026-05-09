<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Backfill week_start_date, week_number, and year from date_debut
        DB::statement('
            UPDATE emplois_temps
            SET
                week_start_date = DATE_SUB(DATE(date_debut), INTERVAL (WEEKDAY(DATE(date_debut)) + 1) % 7 DAY),
                week_number = WEEK(DATE(date_debut), 3),
                year = YEAR(DATE(date_debut))
            WHERE date_debut IS NOT NULL AND (week_start_date IS NULL OR week_number IS NULL OR year IS NULL)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset week fields to NULL
        DB::statement('
            UPDATE emplois_temps
            SET week_start_date = NULL, week_number = NULL, year = NULL
        ');
    }
};
