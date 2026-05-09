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
        if (Schema::hasTable('matieres')) Schema::table('matieres', function (Blueprint $table) {
            // Add missing fields
            $table->text('description')->nullable()->after('libelle');
            $table->decimal('note_max', 5, 2)->nullable()->after('coefficient');
            $table->foreignId('niveau_id')->nullable()->after('note_max')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->after('niveau_id')->nullOnDelete();
            $table->foreignId('cycle_id')->nullable()->after('section_id')->nullOnDelete();
            $table->foreignId('annee_scolaire_id')->nullable()->after('cycle_id')->nullOnDelete();
            $table->foreignId('pays_id')->nullable()->after('annee_scolaire_id')->nullOnDelete();
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
