<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute `fonction_id` (FK → fonctions) sur `enseignants`.
 * Sert à indiquer la fonction/poste hiérarchique de l'enseignant
 * (ex: Professeur titulaire, Coordinateur, Directeur d'études...).
 *
 * Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (Schema::hasTable('enseignants') && !Schema::hasColumn('enseignants', 'fonction_id')) {
            Schema::table('enseignants', function (Blueprint $table) {
                $table->foreignId('fonction_id')->nullable()
                    ->after('teacher_category')
                    ->constrained('fonctions')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('enseignants') && Schema::hasColumn('enseignants', 'fonction_id')) {
            Schema::table('enseignants', function (Blueprint $table) {
                $table->dropForeign(['fonction_id']);
                $table->dropColumn('fonction_id');
            });
        }
    }
};
