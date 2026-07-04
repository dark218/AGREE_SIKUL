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
        if (Schema::hasTable('bibliotheques')) if (Schema::hasTable('bibliotheques')) Schema::table('bibliotheques', function (Blueprint $table) {
            // Ajouter colonne niveau_id
            $table->foreignId('niveau_id')->nullable()->after('langue')->constrained('niveaux')->onDelete('set null');
            // Supprimer l'ancienne colonne texte
            $table->dropColumn('niveau');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bibliotheques')) if (Schema::hasTable('bibliotheques')) Schema::table('bibliotheques', function (Blueprint $table) {
            // Restaurer l'ancienne colonne texte
            $table->string('niveau')->nullable();
            // Supprimer la FK
            $table->dropForeignIdFor('Bibliotheque', 'niveau_id');
            $table->dropColumn('niveau_id');
        });
    }
};
