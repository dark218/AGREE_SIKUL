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
        if (Schema::hasTable('apprenants')) if (Schema::hasTable('apprenants')) Schema::table('apprenants', function (Blueprint $table) {
            // Modify the statut enum to match the form options and use 'inactif' instead of 'non_actif'
            $table->enum('statut', ['actif', 'inactif', 'suspendu', 'exclus'])
                ->default('actif')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('apprenants')) if (Schema::hasTable('apprenants')) Schema::table('apprenants', function (Blueprint $table) {
            // Revert to original enum values
            $table->enum('statut', ['non_actif', 'actif', 'suspendu'])
                ->default('actif')
                ->change();
        });
    }
};
