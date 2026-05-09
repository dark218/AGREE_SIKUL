<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inscriptions')) Schema::table('inscriptions', function (Blueprint $table) {
            // Affectation scolaire étendue
            $table->foreignId('ecole_id')->nullable()->after('classe_id')->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->after('ecole_id')->nullOnDelete();
            $table->foreignId('institution_id')->nullable()->after('campus_id')->nullOnDelete();
            $table->boolean('premiere_inscription')->default(false)->after('institution_id');

            // Frais (montants de base)
            $table->decimal('frais_dossier', 12, 2)->default(0)->after('premiere_inscription');
            $table->decimal('frais_inscription', 12, 2)->default(0)->after('frais_dossier');
            $table->decimal('frais_scolarite', 12, 2)->default(0)->after('frais_inscription');

            // Frais payés
            $table->decimal('frais_dossier_paye', 12, 2)->default(0)->after('frais_scolarite');
            $table->decimal('frais_inscription_paye', 12, 2)->default(0)->after('frais_dossier_paye');
            $table->decimal('frais_scolarite_paye', 12, 2)->default(0)->after('frais_inscription_paye');

            // Dossier
            $table->boolean('dossier_complet')->default(false)->after('frais_scolarite_paye');

            // Documents (chemins fichiers)
            $table->string('fiche_inscription')->nullable()->after('dossier_complet');
            $table->string('carnet_vaccination')->nullable()->after('fiche_inscription');
            $table->string('photos_4x4')->nullable()->after('carnet_vaccination');
            $table->string('copie_acte_naissance')->nullable()->after('photos_4x4');
            $table->string('piece1')->nullable()->after('copie_acte_naissance');
            $table->string('piece2')->nullable()->after('piece1');
            $table->string('piece3')->nullable()->after('piece2');
            $table->string('piece4')->nullable()->after('piece3');
        });
    }

    public function down(): void
    {
        // Rollback not needed
    }
};
