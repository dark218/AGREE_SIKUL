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
        if (Schema::hasTable('notes')) if (Schema::hasTable('notes')) Schema::table('notes', function (Blueprint $table) {
            // Affectation scolaire
            $table->foreignId('annee_scolaire_id')->nullable()->after('apprenant_id')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->after('annee_scolaire_id')->nullOnDelete();
            $table->foreignId('cycle_id')->nullable()->after('section_id')->nullOnDelete();
            $table->foreignId('classe_id')->nullable()->after('cycle_id')->nullOnDelete();
            $table->foreignId('ecole_id')->nullable()->after('classe_id')->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->after('ecole_id')->nullOnDelete();

            // Examen
            $table->foreignId('periode_id')->nullable()->after('campus_id')->nullOnDelete();
            $table->foreignId('nature_examen_id')->nullable()->after('periode_id')->nullOnDelete();
            $table->foreignId('type_examen_id')->nullable()->after('nature_examen_id')->nullOnDelete();
            $table->date('date_examen')->nullable()->after('type_examen_id');

            // Matière et groupe
            $table->foreignId('matiere_id')->nullable()->after('date_examen')->nullOnDelete();
            $table->foreignId('groupe_id')->nullable()->after('matiere_id')->nullOnDelete();

            // Résultats
            $table->decimal('note_max', 5, 2)->nullable()->after('note');
            $table->decimal('coefficient', 5, 2)->nullable()->after('note_max');
            $table->integer('rang')->nullable()->after('coefficient');

            // Enseignant
            $table->foreignId('enseignant_id')->nullable()->after('rang')->nullOnDelete();

            // Statut et remarques
            $table->string('statut')->default('actif')->after('enseignant_id');
            $table->string('remarques')->nullable()->after('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notes')) if (Schema::hasTable('notes')) Schema::table('notes', function (Blueprint $table) {
            // Foreign keys dropped

            $table->dropColumn([
                'annee_scolaire_id', 'section_id', 'cycle_id', 'classe_id', 'ecole_id', 'campus_id',
                'periode_id', 'nature_examen_id', 'type_examen_id', 'date_examen',
                'matiere_id', 'groupe_id', 'note_max', 'coefficient', 'rang', 'enseignant_id', 'statut', 'remarques'
            ]);
        });
    }
};
