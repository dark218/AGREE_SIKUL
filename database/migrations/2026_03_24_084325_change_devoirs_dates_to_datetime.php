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
        Schema::table('devoirs', function (Blueprint $table) {
            // Change date_debut from date to dateTime
            if (Schema::hasColumn('devoirs', 'date_debut')) {
                $table->dateTime('date_debut')->nullable()->change();
            }

            // Change date_fin from date to dateTime
            if (Schema::hasColumn('devoirs', 'date_fin')) {
                $table->dateTime('date_fin')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devoirs', function (Blueprint $table) {
            // Change back to date columns
            if (Schema::hasColumn('devoirs', 'date_debut')) {
                $table->date('date_debut')->nullable()->change();
            }

            if (Schema::hasColumn('devoirs', 'date_fin')) {
                $table->date('date_fin')->nullable()->change();
            }
        });
    }
};
