<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('inventaires')) if (Schema::hasTable('inventaires')) Schema::table('inventaires', function (Blueprint $table) {
            // Ajouter directement le nouvel index unique
            $table->unique(
                ['emplacement_id', 'date_inventaire', 'statut', 'deleted_at'],
                'unique_inventaire_actif'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inventaires')) if (Schema::hasTable('inventaires')) Schema::table('inventaires', function (Blueprint $table) {
            $table->dropUnique('unique_inventaire_actif');
            $table->unique(['emplacement_id', 'date_inventaire'], 'inventaires_emplacement_id_date_inventaire_unique');
        });
    }
};
