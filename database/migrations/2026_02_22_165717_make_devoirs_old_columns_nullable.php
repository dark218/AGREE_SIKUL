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
        Schema::table('devoirs', function (Blueprint $table) {
            // Make old columns nullable since we're using new columns instead
            // date_donnee and date_limite from old schema
            if (Schema::hasColumn('devoirs', 'date_donnee')) {
                $table->date('date_donnee')->nullable()->change();
            }

            if (Schema::hasColumn('devoirs', 'date_limite')) {
                $table->date('date_limite')->nullable()->change();
            }

            if (Schema::hasColumn('devoirs', 'fichier_enonce_id')) {
                $table->unsignedBigInteger('fichier_enonce_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devoirs', function (Blueprint $table) {
            if (Schema::hasColumn('devoirs', 'date_donnee')) {
                $table->date('date_donnee')->nullable(false)->change();
            }

            if (Schema::hasColumn('devoirs', 'date_limite')) {
                $table->date('date_limite')->nullable(false)->change();
            }

            if (Schema::hasColumn('devoirs', 'fichier_enonce_id')) {
                $table->unsignedBigInteger('fichier_enonce_id')->nullable(false)->change();
            }
        });
    }
};
