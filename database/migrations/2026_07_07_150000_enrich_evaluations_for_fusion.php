<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FUSION #10 (§10.1) — Enrichit `evaluations` pour accueillir Devoir,
 * ExamenEnLigne et PlanificationExamen via discriminant `type`.
 *
 * Colonnes ajoutées (nullable, idempotentes) :
 *   - description        (longtext)  — de Devoir
 *   - cours_id           (FK cours)  — de Devoir
 *   - date_debut, date_fin, heure_debut, heure_fin (datetime/time)
 *                                   — de Devoir/PlanificationExamen
 *   - fichier_enonce_id  (FK fichier) — de Devoir
 *   - nature_examen_id, type_examen_id — de PlanificationExamen
 *   - duree_minutes, note_maximum, note_minimum_passage — de ExamenEnLigne
 *   - melange_questions, melange_reponses, retour_arriere, afficher_resultat,
 *     afficher_correction (boolean) — de ExamenEnLigne
 *   - nombre_tentatives, mot_de_passe — de ExamenEnLigne
 *   - instructions (text) — de ExamenEnLigne
 *
 * Après cette migration, une évaluation avec `type='examen_en_ligne'` porte
 * les mêmes attributs qu'un ExamenEnLigne existant, prête pour la fusion des
 * données via EvaluationsFusionService.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('evaluations')) return;

        Schema::table('evaluations', function (Blueprint $t) {
            // Devoir
            if (!Schema::hasColumn('evaluations', 'description')) {
                $t->longText('description')->nullable()->after('titre');
            }
            if (!Schema::hasColumn('evaluations', 'cours_id')) {
                $t->unsignedBigInteger('cours_id')->nullable()->after('classe_id');
            }
            if (!Schema::hasColumn('evaluations', 'date_debut')) {
                $t->dateTime('date_debut')->nullable()->after('date');
            }
            if (!Schema::hasColumn('evaluations', 'date_fin')) {
                $t->dateTime('date_fin')->nullable()->after('date_debut');
            }
            if (!Schema::hasColumn('evaluations', 'heure_debut')) {
                $t->time('heure_debut')->nullable()->after('date_fin');
            }
            if (!Schema::hasColumn('evaluations', 'heure_fin')) {
                $t->time('heure_fin')->nullable()->after('heure_debut');
            }
            if (!Schema::hasColumn('evaluations', 'fichier_enonce_id')) {
                $t->unsignedBigInteger('fichier_enonce_id')->nullable()->after('sur');
            }

            // PlanificationExamen
            if (!Schema::hasColumn('evaluations', 'nature_examen_id')) {
                $t->unsignedBigInteger('nature_examen_id')->nullable()->after('fichier_enonce_id');
            }
            if (!Schema::hasColumn('evaluations', 'type_examen_id')) {
                $t->unsignedBigInteger('type_examen_id')->nullable()->after('nature_examen_id');
            }

            // ExamenEnLigne
            if (!Schema::hasColumn('evaluations', 'duree_minutes')) {
                $t->decimal('duree_minutes', 8, 2)->nullable()->after('duree');
            }
            if (!Schema::hasColumn('evaluations', 'note_maximum')) {
                $t->decimal('note_maximum', 8, 2)->nullable()->after('sur');
            }
            if (!Schema::hasColumn('evaluations', 'note_minimum_passage')) {
                $t->decimal('note_minimum_passage', 8, 2)->nullable()->after('note_maximum');
            }
            if (!Schema::hasColumn('evaluations', 'nombre_tentatives')) {
                $t->unsignedInteger('nombre_tentatives')->nullable()->after('note_minimum_passage');
            }
            if (!Schema::hasColumn('evaluations', 'melange_questions')) {
                $t->boolean('melange_questions')->default(false)->after('nombre_tentatives');
            }
            if (!Schema::hasColumn('evaluations', 'melange_reponses')) {
                $t->boolean('melange_reponses')->default(false)->after('melange_questions');
            }
            if (!Schema::hasColumn('evaluations', 'retour_arriere')) {
                $t->boolean('retour_arriere')->default(true)->after('melange_reponses');
            }
            if (!Schema::hasColumn('evaluations', 'afficher_resultat')) {
                $t->boolean('afficher_resultat')->default(true)->after('retour_arriere');
            }
            if (!Schema::hasColumn('evaluations', 'afficher_correction')) {
                $t->boolean('afficher_correction')->default(false)->after('afficher_resultat');
            }
            if (!Schema::hasColumn('evaluations', 'mot_de_passe')) {
                $t->string('mot_de_passe', 125)->nullable()->after('afficher_correction');
            }
            if (!Schema::hasColumn('evaluations', 'instructions')) {
                $t->text('instructions')->nullable()->after('description');
            }
            if (!Schema::hasColumn('evaluations', 'enseignant_id')) {
                $t->unsignedBigInteger('enseignant_id')->nullable()->after('cours_id');
            }
        });
    }

    public function down(): void
    {
        // Aucun rollback — retirer des colonnes après un usage prod potentiel
        // détruirait des données. Rollback manuel si vraiment nécessaire.
    }
};
