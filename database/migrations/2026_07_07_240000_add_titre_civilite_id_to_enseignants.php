<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le formulaire enseignant proposait un champ « Titre de civilité » sans colonne
 * de stockage : la valeur ne se sauvegardait ni ne se rechargeait en édition.
 * On ajoute enseignants.titre_civilite_id (FK vers titres_civilites).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('enseignants') && !Schema::hasColumn('enseignants', 'titre_civilite_id')) {
            Schema::table('enseignants', function (Blueprint $table) {
                $table->foreignId('titre_civilite_id')->nullable()->after('nom_jeune_fille')
                    ->constrained('titres_civilites')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('enseignants') && Schema::hasColumn('enseignants', 'titre_civilite_id')) {
            Schema::table('enseignants', function (Blueprint $table) {
                $table->dropConstrainedForeignId('titre_civilite_id');
            });
        }
    }
};
