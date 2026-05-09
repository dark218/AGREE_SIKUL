<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajouter les colonnes manquantes à examens_en_ligne
        // Note: enseignant_id et nombre_heures existent déjà dans la table
        if (Schema::hasTable('examens_en_ligne') && !Schema::hasColumn('examens_en_ligne', 'melange_questions')) {
            Schema::table('examens_en_ligne', function (Blueprint $table) {
                // Paramètres d'examen
                $table->boolean('melange_questions')->default(false)->after('note_minimum_passage');
                $table->boolean('melange_reponses')->default(false)->after('melange_questions');
                $table->integer('nombre_tentatives')->default(1)->after('melange_reponses');
                $table->boolean('afficher_resultat')->default(true)->after('nombre_tentatives');
                $table->boolean('afficher_correction')->default(false)->after('afficher_resultat');
                $table->boolean('retour_arriere')->default(true)->after('afficher_correction');
                $table->string('mot_de_passe')->nullable()->after('retour_arriere');
                $table->text('instructions')->nullable()->after('description');
            });
        }

        // Modifier la colonne etat pour supporter plus de statuts
        // On utilise une approche compatible MySQL
        if (Schema::hasTable('examens_en_ligne') && Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement("ALTER TABLE examens_en_ligne MODIFY COLUMN etat VARCHAR(20) DEFAULT 'brouillon'");
        } else {
            // SQLite fallback
            Schema::table('examens_en_ligne', function (Blueprint $table) {
                $table->string('etat', 20)->default('brouillon')->change();
            });
        }

        // 2. Table des questions d'examen
        if (!Schema::hasTable('questions_examen')) Schema::create('questions_examen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_en_ligne_id')->constrained('examens_en_ligne')->onDelete('cascade');
            $table->integer('ordre')->default(0);
            $table->enum('type', ['qcm', 'vrai_faux', 'reponse_libre', 'texte_trous'])->default('qcm');
            $table->text('enonce');
            $table->text('explication')->nullable();
            $table->string('image')->nullable();
            $table->decimal('points', 5, 2)->default(1);
            $table->boolean('obligatoire')->default(true);
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('examen_en_ligne_id');
            $table->index('ordre');
        });

        // 3. Table des réponses (choix) pour chaque question
        if (!Schema::hasTable('reponses_question')) Schema::create('reponses_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_examen_id')->constrained('questions_examen')->onDelete('cascade');
            $table->integer('ordre')->default(0);
            $table->text('texte');
            $table->boolean('est_correcte')->default(false);
            $table->text('explication')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('question_examen_id');
        });

        // 4. Table des tentatives d'examen par apprenant
        if (!Schema::hasTable('tentatives_examen')) Schema::create('tentatives_examen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_en_ligne_id')->constrained('examens_en_ligne')->onDelete('cascade');
            $table->foreignId('apprenant_id')->constrained('apprenants')->onDelete('cascade');
            $table->integer('numero_tentative')->default(1);
            $table->dateTime('debut_composition')->nullable();
            $table->dateTime('fin_composition')->nullable();
            $table->decimal('temps_passe_minutes', 7, 2)->nullable();
            $table->decimal('score', 7, 2)->nullable();
            $table->decimal('pourcentage', 5, 2)->nullable();
            $table->integer('questions_repondues')->default(0);
            $table->integer('reponses_correctes')->default(0);
            $table->enum('statut', ['en_cours', 'soumise', 'corrigee', 'abandonnee'])->default('en_cours');
            $table->string('ip_address')->nullable();
            $table->string('creation_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['examen_en_ligne_id', 'apprenant_id']);
            $table->index('statut');
        });

        // 5. Table des réponses de l'apprenant à chaque question
        if (!Schema::hasTable('reponses_apprenant')) Schema::create('reponses_apprenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tentative_examen_id')->constrained('tentatives_examen')->onDelete('cascade');
            $table->foreignId('question_examen_id')->constrained('questions_examen')->onDelete('cascade');
            $table->foreignId('reponse_choisie_id')->nullable()->constrained('reponses_question')->onDelete('set null');
            $table->text('reponse_libre')->nullable();
            $table->boolean('est_correcte')->nullable();
            $table->decimal('points_obtenus', 5, 2)->nullable();
            $table->timestamps();
            $table->index(['tentative_examen_id', 'question_examen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reponses_apprenant');
        Schema::dropIfExists('tentatives_examen');
        Schema::dropIfExists('reponses_question');
        Schema::dropIfExists('questions_examen');

        Schema::table('examens_en_ligne', function (Blueprint $table) {
            $table->dropColumn([
                'melange_questions', 'melange_reponses',
                'nombre_tentatives', 'afficher_resultat',
                'afficher_correction', 'retour_arriere',
                'mot_de_passe', 'instructions'
            ]);
        });
    }
};
