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
        if (Schema::hasTable('apprenants')) Schema::table('apprenants', function (Blueprint $table) {
            $table->foreignId('annee_scolaire_id')->nullable()->nullOnDelete();
            $table->foreignId('type_apprenant_id')->nullable()->nullOnDelete();
            $table->foreignId('categorie_apprenant_id')->nullable()->nullOnDelete();
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
