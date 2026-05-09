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
            $table->date('date_inscription')->nullable()->after('apprenant_id');
            $table->date('date_debut')->nullable()->after('date_inscription');
            $table->date('date_fin')->nullable()->after('date_debut');
            $table->integer('nombre_jours')->nullable()->after('date_fin');
            $table->text('observations')->nullable()->after('nombre_jours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inscriptions_cantine')) if (Schema::hasTable('inscriptions_cantine')) Schema::table('inscriptions_cantine', function (Blueprint $table) {
            $table->dropColumn(['date_inscription', 'date_debut', 'date_fin', 'nombre_jours', 'observations']);
        });
    }
};
