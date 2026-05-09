<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip si la table n'existe pas ou la colonne est déjà renommée
        if (!Schema::hasTable('reservations') || !Schema::hasColumn('reservations', 'utilisateur_id')) {
            return;
        }

        // Vérifier si la FK existe avant de la supprimer
        $fkExists = \DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'reservations'
            AND CONSTRAINT_NAME = 'reservations_utilisateur_id_foreign'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        if ($fkExists[0]->cnt > 0) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropForeign('reservations_utilisateur_id_foreign');
            });
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->renameColumn('utilisateur_id', 'apprenant_id');
        });

        // Ajouter la nouvelle FK
        $fkNewExists = \DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'reservations'
            AND CONSTRAINT_NAME = 'reservations_apprenant_id_foreign'
        ");

        if ($fkNewExists[0]->cnt == 0) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreign('apprenant_id')->references('id')->on('apprenants')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Drop new foreign key
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['apprenant_id']);
        });

        // Rename column back
        Schema::table('reservations', function (Blueprint $table) {
            $table->renameColumn('apprenant_id', 'utilisateur_id');
        });

        // Add back old foreign key
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreign('utilisateur_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
