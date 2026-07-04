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
        if (Schema::hasTable('enseignants')) if (Schema::hasTable('enseignants')) Schema::table('enseignants', function (Blueprint $table) {
            // Modify the statut enum to include 'inactif'
            $table->enum('statut', ['actif', 'inactif', 'suspendu', 'conge', 'retraite'])
                ->default('actif')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('enseignants')) if (Schema::hasTable('enseignants')) Schema::table('enseignants', function (Blueprint $table) {
            // Revert to original enum values
            $table->enum('statut', ['actif', 'suspendu', 'conge', 'retraite'])
                ->default('actif')
                ->change();
        });
    }
};
