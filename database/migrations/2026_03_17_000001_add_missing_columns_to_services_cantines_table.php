<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services_cantines', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('services_cantines', 'code')) {
                $table->string('code')->nullable()->unique();
            }
            if (!Schema::hasColumn('services_cantines', 'prix_cents')) {
                $table->integer('prix_cents')->nullable();
            }
            if (!Schema::hasColumn('services_cantines', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('services_cantines', 'annee_scolaire_id')) {
                $table->unsignedBigInteger('annee_scolaire_id')->nullable();
            }
            if (!Schema::hasColumn('services_cantines', 'niveau_id')) {
                $table->unsignedBigInteger('niveau_id')->nullable();
            }
            if (!Schema::hasColumn('services_cantines', 'cycle_enseignement_id')) {
                $table->unsignedBigInteger('cycle_enseignement_id')->nullable();
            }
            if (!Schema::hasColumn('services_cantines', 'campus_id')) {
                $table->unsignedBigInteger('campus_id')->nullable();
            }
            if (!Schema::hasColumn('services_cantines', 'tarif_mensuel')) {
                $table->integer('tarif_mensuel')->nullable();
            }
            if (!Schema::hasColumn('services_cantines', 'tarif_trimestriel')) {
                $table->integer('tarif_trimestriel')->nullable();
            }
            if (!Schema::hasColumn('services_cantines', 'tarif_semestriel')) {
                $table->integer('tarif_semestriel')->nullable();
            }
            if (!Schema::hasColumn('services_cantines', 'tarif_annuel')) {
                $table->integer('tarif_annuel')->nullable();
            }
            if (!Schema::hasColumn('services_cantines', 'date_debut')) {
                $table->date('date_debut')->nullable();
            }
            if (!Schema::hasColumn('services_cantines', 'date_fin')) {
                $table->date('date_fin')->nullable();
            }
            if (!Schema::hasColumn('services_cantines', 'statut')) {
                $table->enum('statut', ['actif', 'inactif'])->default('actif');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services_cantines', function (Blueprint $table) {
            $columns = ['code', 'prix_cents', 'description', 'annee_scolaire_id', 'niveau_id',
                       'cycle_enseignement_id', 'campus_id', 'tarif_mensuel', 'tarif_trimestriel',
                       'tarif_semestriel', 'tarif_annuel', 'date_debut', 'date_fin', 'statut'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('services_cantines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
