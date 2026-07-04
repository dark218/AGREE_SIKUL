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
        Schema::table('cours', function (Blueprint $table) {
            // Ajouter les colonnes manquantes si elles n'existent pas
            if (!Schema::hasColumn('cours', 'code')) {
                $table->string('code', 100)->unique()->after('enseignant_id');
            }
            if (!Schema::hasColumn('cours', 'titre')) {
                $table->string('titre', 255)->after('code');
            }
            if (!Schema::hasColumn('cours', 'description')) {
                $table->text('description')->nullable()->after('titre');
            }
            if (!Schema::hasColumn('cours', 'date_debut')) {
                $table->date('date_debut')->after('description');
            }
            if (!Schema::hasColumn('cours', 'date_fin')) {
                $table->date('date_fin')->after('date_debut');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cours', function (Blueprint $table) {
            // Supprimer les colonnes ajoutées
            $table->dropColumn(['code', 'titre', 'description', 'date_debut', 'date_fin']);
        });
    }
};
