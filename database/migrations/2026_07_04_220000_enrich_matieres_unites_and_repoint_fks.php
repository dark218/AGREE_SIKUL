<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.1 — Suppression fonctionnalité Matiere (Academique) au profit
 * de MatiereUnite (Parametrage).
 *
 * 1. Enrichit `matieres_unites` des colonnes présentes dans `matieres`
 *    (description, annee_scolaire_id, pays_id, couleur, categorie).
 * 2. Copie les enregistrements de `matieres` vers `matieres_unites`
 *    (les deux tables sont vides au moment de cette migration → no-op).
 * 3. Repointe les FK `matiere_id` de 8 tables vers `matieres_unites`.
 *    Pour `affectations_enseignants`, boucle sur 21 colonnes.
 *
 * Idempotente. Ne drop PAS la table `matieres` ni son controller — c'est
 * fait dans une migration séparée après vérification manuelle.
 */
return new class extends Migration
{
    private const REPOINT_TABLES_SIMPLE = [
        'cours',
        'moyennes_matieres',
        'devoirs',
        'absences_apprenants',
        'absences_enseignants',
        'evaluations',
        'enseignant_matieres',
    ];

    public function up(): void
    {
        // 1. Enrichir matieres_unites — colonnes présentes dans matieres.
        if (Schema::hasTable('matieres_unites')) {
            Schema::table('matieres_unites', function (Blueprint $table) {
                if (!Schema::hasColumn('matieres_unites', 'description')) {
                    $table->text('description')->nullable()->after('libelle');
                }
                if (!Schema::hasColumn('matieres_unites', 'annee_scolaire_id')) {
                    $table->foreignId('annee_scolaire_id')->nullable()->after('cycle_id')
                        ->constrained('annees_scolaires')->nullOnDelete();
                }
                if (!Schema::hasColumn('matieres_unites', 'pays_id')) {
                    $table->foreignId('pays_id')->nullable()->after('annee_scolaire_id')
                        ->constrained('pays')->nullOnDelete();
                }
                if (!Schema::hasColumn('matieres_unites', 'couleur')) {
                    $table->string('couleur', 125)->nullable()->after('pays_id');
                }
                if (!Schema::hasColumn('matieres_unites', 'categorie')) {
                    $table->string('categorie', 125)->nullable()->after('couleur');
                }
            });
        }

        // 2. Copie éventuelle des données (matieres → matieres_unites).
        //    Skip si `matieres` inexistante ou vide.
        if (Schema::hasTable('matieres')) {
            $existing = DB::table('matieres_unites')->pluck('code')->toArray();
            $rows = DB::table('matieres')->whereNull('deleted_at')->get();
            foreach ($rows as $row) {
                if (in_array($row->code, $existing, true)) {
                    continue; // dédoublonnage
                }
                DB::table('matieres_unites')->insert([
                    'code'              => $row->code,
                    'libelle'           => $row->libelle,
                    'description'       => $row->description ?? null,
                    'ecole_id'          => $row->ecole_id ?? null,
                    'niveau_id'         => $row->niveau_id ?? null,
                    'section_id'        => $row->section_id ?? null,
                    'cycle_id'          => $row->cycle_id ?? null,
                    'annee_scolaire_id' => $row->annee_scolaire_id ?? null,
                    'pays_id'           => $row->pays_id ?? null,
                    'couleur'           => $row->couleur ?? null,
                    'categorie'         => $row->categorie ?? null,
                    'coefficient'       => $row->coefficient ?? 1,
                    'note_max'          => $row->note_max ?? 20,
                    'etat'              => $row->statut ?? 'actif', // mapping statut → etat
                    'created_at'        => $row->created_at ?? now(),
                    'updated_at'        => $row->updated_at ?? now(),
                ]);
            }
        }

        // 3. Repointer les FK sur matieres_unites.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::REPOINT_TABLES_SIMPLE as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'matiere_id')) {
                continue;
            }
            $this->dropForeignIfExists($tableName, 'matiere_id');
            Schema::table($tableName, function (Blueprint $t) use ($tableName) {
                // Certaines tables ont onDelete('cascade'), d'autres 'set null' — on met
                // 'set null' partout pour éviter la perte d'historique.
                $t->foreign('matiere_id')
                    ->references('id')->on('matieres_unites')
                    ->nullOnDelete();
            });
        }

        // affectations_enseignants : 21 colonnes matiere_1_id .. matiere_21_id
        if (Schema::hasTable('affectations_enseignants')) {
            for ($i = 1; $i <= 21; $i++) {
                $col = "matiere_{$i}_id";
                if (!Schema::hasColumn('affectations_enseignants', $col)) {
                    continue;
                }
                $this->dropForeignIfExists('affectations_enseignants', $col);
                Schema::table('affectations_enseignants', function (Blueprint $t) use ($col) {
                    $t->foreign($col)
                        ->references('id')->on('matieres_unites')
                        ->nullOnDelete();
                });
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Rollback non trivial (repointer vers matieres, réajuster nullable) —
        // à faire manuellement si besoin.
    }

    /**
     * Drop une FK si elle existe (les FK d'auto-migrations n'ont pas toujours
     * le même nom, ex: `cours_matiere_id_foreign` vs custom). On cherche par
     * COLUMN_NAME dans INFORMATION_SCHEMA.
     */
    private function dropForeignIfExists(string $tableName, string $columnName): void
    {
        $connection = DB::connection()->getDatabaseName();
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$connection, $tableName, $columnName]
        );
        foreach ($constraints as $c) {
            try {
                DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$c->CONSTRAINT_NAME}`");
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
};
