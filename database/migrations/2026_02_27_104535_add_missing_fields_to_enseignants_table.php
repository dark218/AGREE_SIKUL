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
        if (Schema::hasTable('enseignants')) Schema::table('enseignants', function (Blueprint $table) {
            $table->string('nom_restituer')->nullable();
            $table->string('nom_jeune_fille')->nullable();
            $table->string('nationalite')->nullable();
            $table->foreignId('categorie_enseignant_id')->nullable()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback not needed
    }
};
