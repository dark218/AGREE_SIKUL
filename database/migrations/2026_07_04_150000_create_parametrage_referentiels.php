<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Création groupée des 8 référentiels métier paramétrables identifiés par l'audit :
 *   • types_contrats
 *   • statuts_employes
 *   • situations_matrimoniales
 *   • liens_parente
 *   • civilites
 *   • statuts_apprenants
 *   • types_inscriptions
 *   • groupes_sanguins
 *   • langues
 *
 * Tables schéma-uniforme : code (unique), libelle, ordre, etat, softDeletes.
 * Seed initial pour chaque table avec les valeurs actuellement hard-codées.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'types_contrats' => [
                ['code' => 'CDI', 'libelle' => 'CDI', 'ordre' => 1],
                ['code' => 'CDD', 'libelle' => 'CDD', 'ordre' => 2],
                ['code' => 'VACATAIRE', 'libelle' => 'Vacataire', 'ordre' => 3],
                ['code' => 'AUTRE', 'libelle' => 'Autre', 'ordre' => 4],
            ],
            'statuts_employes' => [
                ['code' => 'ACTIF', 'libelle' => 'Actif', 'ordre' => 1],
                ['code' => 'SUSPENDU', 'libelle' => 'Suspendu', 'ordre' => 2],
                ['code' => 'CONGE', 'libelle' => 'En congé', 'ordre' => 3],
                ['code' => 'RETRAITE', 'libelle' => 'Retraité', 'ordre' => 4],
                ['code' => 'DEMISSION', 'libelle' => 'Démissionné', 'ordre' => 5],
            ],
            'situations_matrimoniales' => [
                ['code' => 'CELIBATAIRE', 'libelle' => 'Célibataire', 'ordre' => 1],
                ['code' => 'MARIE', 'libelle' => 'Marié(e)', 'ordre' => 2],
                ['code' => 'DIVORCE', 'libelle' => 'Divorcé(e)', 'ordre' => 3],
                ['code' => 'VEUF', 'libelle' => 'Veuf/Veuve', 'ordre' => 4],
            ],
            'liens_parente' => [
                ['code' => 'PERE', 'libelle' => 'Père', 'ordre' => 1],
                ['code' => 'MERE', 'libelle' => 'Mère', 'ordre' => 2],
                ['code' => 'TUTEUR_LEGAL', 'libelle' => 'Tuteur légal', 'ordre' => 3],
                ['code' => 'GRAND_PARENT', 'libelle' => 'Grand-parent', 'ordre' => 4],
                ['code' => 'ONCLE', 'libelle' => 'Oncle', 'ordre' => 5],
                ['code' => 'TANTE', 'libelle' => 'Tante', 'ordre' => 6],
                ['code' => 'FRERE', 'libelle' => 'Frère', 'ordre' => 7],
                ['code' => 'SOEUR', 'libelle' => 'Sœur', 'ordre' => 8],
                ['code' => 'COUSIN', 'libelle' => 'Cousin(e)', 'ordre' => 9],
                ['code' => 'AUTRE', 'libelle' => 'Autre', 'ordre' => 10],
            ],
            'civilites' => [
                ['code' => 'MR', 'libelle' => 'M.', 'ordre' => 1],
                ['code' => 'MME', 'libelle' => 'Mme', 'ordre' => 2],
                ['code' => 'MLLE', 'libelle' => 'Mlle', 'ordre' => 3],
            ],
            'statuts_apprenants' => [
                ['code' => 'ACTIF', 'libelle' => 'Actif', 'ordre' => 1],
                ['code' => 'SUSPENDU', 'libelle' => 'Suspendu', 'ordre' => 2],
                ['code' => 'EXCLU', 'libelle' => 'Exclu', 'ordre' => 3],
                ['code' => 'DIPLOME', 'libelle' => 'Diplômé', 'ordre' => 4],
                ['code' => 'ABANDONNE', 'libelle' => 'Abandonné', 'ordre' => 5],
            ],
            'types_inscriptions' => [
                ['code' => 'NOUVEAU', 'libelle' => 'Nouveau', 'ordre' => 1],
                ['code' => 'REDOUBLEMENT', 'libelle' => 'Redoublement', 'ordre' => 2],
                ['code' => 'TRANSFERT', 'libelle' => 'Transfert', 'ordre' => 3],
                ['code' => 'REPRISE', 'libelle' => 'Reprise', 'ordre' => 4],
            ],
            'groupes_sanguins' => [
                ['code' => 'O_POS', 'libelle' => 'O+', 'ordre' => 1],
                ['code' => 'O_NEG', 'libelle' => 'O-', 'ordre' => 2],
                ['code' => 'A_POS', 'libelle' => 'A+', 'ordre' => 3],
                ['code' => 'A_NEG', 'libelle' => 'A-', 'ordre' => 4],
                ['code' => 'B_POS', 'libelle' => 'B+', 'ordre' => 5],
                ['code' => 'B_NEG', 'libelle' => 'B-', 'ordre' => 6],
                ['code' => 'AB_POS', 'libelle' => 'AB+', 'ordre' => 7],
                ['code' => 'AB_NEG', 'libelle' => 'AB-', 'ordre' => 8],
            ],
            'langues' => [
                ['code' => 'FR', 'libelle' => 'Français', 'ordre' => 1],
                ['code' => 'EN', 'libelle' => 'Anglais', 'ordre' => 2],
                ['code' => 'AR', 'libelle' => 'Arabe', 'ordre' => 3],
                ['code' => 'PT', 'libelle' => 'Portugais', 'ordre' => 4],
                ['code' => 'ES', 'libelle' => 'Espagnol', 'ordre' => 5],
                ['code' => 'AUTRE', 'libelle' => 'Autre', 'ordre' => 99],
            ],
        ];

        foreach ($tables as $tableName => $seed) {
            if (!Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $t) {
                    $t->id();
                    $t->string('code', 50)->unique();
                    $t->string('libelle', 150);
                    $t->integer('ordre')->default(0);
                    $t->enum('etat', ['actif', 'inactif'])->default('actif');
                    // Traçabilité BaseModel
                    $t->string('creation_username')->nullable();
                    $t->string('creation_hostname')->nullable();
                    $t->string('modification_username')->nullable();
                    $t->string('modification_hostname')->nullable();
                    $t->string('checksum')->nullable();
                    $t->string('external_id')->nullable();
                    $t->string('source_system')->default('Agree Sikul');
                    $t->softDeletes();
                    $t->timestamps();
                });
            }

            // Seed initial (idempotent : ne re-insère pas si code existe déjà)
            if (DB::table($tableName)->count() === 0) {
                foreach ($seed as $row) {
                    DB::table($tableName)->insert(array_merge($row, [
                        'etat' => 'actif',
                        'source_system' => 'Agree Sikul',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
        }
    }

    public function down(): void
    {
        foreach ([
            'types_contrats', 'statuts_employes', 'situations_matrimoniales',
            'liens_parente', 'civilites', 'statuts_apprenants',
            'types_inscriptions', 'groupes_sanguins', 'langues',
        ] as $tableName) {
            Schema::dropIfExists($tableName);
        }
    }
};
