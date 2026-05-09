<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParametrageDataSeeder extends Seeder
{
    public function run(): void
    {
        // Créer données de base pour le Parametrage

        // 1. Create a default Pays if none exists
        if (DB::table('pays')->count() == 0) {
            DB::table('pays')->insert([
                [
                    'code' => 'SN',
                    'libelle' => 'Sénégal',
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
            $this->command->info('✅ Pays par défaut créé');
        }

        // 2. Get the default Pays ID
        $paysId = DB::table('pays')->first()?->id ?? 1;

        // 2b. Create default Institution and Campus if not exist
        if (DB::table('institutions')->count() == 0) {
            $institutionId = DB::table('institutions')->insertGetId([
                'code' => 'INST_DEFAULT',
                'nom' => 'Institution Par Défaut',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info('✅ Institution par défaut créée');
        } else {
            $institutionId = DB::table('institutions')->first()?->id ?? 1;
        }

        if (DB::table('campuses')->count() == 0) {
            DB::table('campuses')->insert([
                [
                    'code' => 'CAMPUS_DEFAULT',
                    'nom' => 'Campus Par Défaut',
                    'institution_id' => $institutionId,
                    'statut' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
            $this->command->info('✅ Campus par défaut créé');
        }

        $campusId = DB::table('campuses')->first()?->id ?? 1;

        // 2c. Create default Ecoles if not exist
        if (DB::table('ecoles')->count() == 0) {
            DB::table('ecoles')->insert([
                [
                    'code' => 'ECOLE_001',
                    'nom' => 'École Primaire 1',
                    'campus_id' => $campusId,
                    'type_enseignement' => 'primaire',
                    'statut' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'ECOLE_002',
                    'nom' => 'École Secondaire 1',
                    'campus_id' => $campusId,
                    'type_enseignement' => 'secondaire',
                    'statut' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
            $this->command->info('✅ Écoles créées');
        }

        // 2d. Create default Sections if not exist
        if (DB::table('sections')->count() == 0) {
            DB::table('sections')->insert([
                [
                    'code' => 'SECTION_01',
                    'libelle' => 'Section A',
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'SECTION_02',
                    'libelle' => 'Section B',
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'SECTION_03',
                    'libelle' => 'Section C',
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
            $this->command->info('✅ Sections créées');
        }

        // 3. Cycles Enseignement
        if (DB::table('cycles_enseignement')->count() == 0) {
            DB::table('cycles_enseignement')->insert([
                [
                    'code' => 'PRIMAIRE',
                    'libelle' => 'Primaire',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'SECONDAIRE1',
                    'libelle' => 'Secondaire 1er Cycle',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'SECONDAIRE2',
                    'libelle' => 'Secondaire 2nd Cycle',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'SUPERIEUR',
                    'libelle' => 'Enseignement Supérieur',
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
            $this->command->info('✅ Cycles d\'enseignement créés');
        }

        // 4. Niveaux Etudes
        if (DB::table('niveaux_etudes')->count() == 0) {
            $cycles = DB::table('cycles_enseignement')->pluck('id', 'code');

            DB::table('niveaux_etudes')->insert([
                [
                    'code' => 'CP',
                    'libelle' => 'Cours Préparatoire',
                    'cycle_id' => $cycles['PRIMAIRE'] ?? 1,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'CE1',
                    'libelle' => 'Cours Élémentaire 1ère année',
                    'cycle_id' => $cycles['PRIMAIRE'] ?? 1,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'CE2',
                    'libelle' => 'Cours Élémentaire 2ème année',
                    'cycle_id' => $cycles['PRIMAIRE'] ?? 1,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'CM1',
                    'libelle' => 'Cours Moyen 1ère année',
                    'cycle_id' => $cycles['PRIMAIRE'] ?? 1,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'CM2',
                    'libelle' => 'Cours Moyen 2ème année',
                    'cycle_id' => $cycles['PRIMAIRE'] ?? 1,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => '6E',
                    'libelle' => 'Sixième',
                    'cycle_id' => $cycles['SECONDAIRE1'] ?? 2,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => '5E',
                    'libelle' => 'Cinquième',
                    'cycle_id' => $cycles['SECONDAIRE1'] ?? 2,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => '4E',
                    'libelle' => 'Quatrième',
                    'cycle_id' => $cycles['SECONDAIRE1'] ?? 2,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => '3E',
                    'libelle' => 'Troisième',
                    'cycle_id' => $cycles['SECONDAIRE1'] ?? 2,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => '2NDE',
                    'libelle' => 'Seconde',
                    'cycle_id' => $cycles['SECONDAIRE2'] ?? 3,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => '1ERE',
                    'libelle' => 'Première',
                    'cycle_id' => $cycles['SECONDAIRE2'] ?? 3,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'TERM',
                    'libelle' => 'Terminale',
                    'cycle_id' => $cycles['SECONDAIRE2'] ?? 3,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'L1',
                    'libelle' => 'Licence 1ère année',
                    'cycle_id' => $cycles['SUPERIEUR'] ?? 4,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'L2',
                    'libelle' => 'Licence 2ème année',
                    'cycle_id' => $cycles['SUPERIEUR'] ?? 4,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'L3',
                    'libelle' => 'Licence 3ème année',
                    'cycle_id' => $cycles['SUPERIEUR'] ?? 4,
                    'pays_id' => $paysId,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
            $this->command->info('✅ Niveaux d\'études créés');
        }

        // 4b. Régions (Senegal)
        if (!DB::table('regions')->where('code', 'DAKAR')->exists()) {
            DB::table('regions')->insert([
                ['code' => 'DAKAR', 'libelle' => 'Dakar', 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'KAOLACK', 'libelle' => 'Kaolack', 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'KOLDA', 'libelle' => 'Kolda', 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'LOUGA', 'libelle' => 'Louga', 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'MATAM', 'libelle' => 'Matam', 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'SAINT_LOUIS', 'libelle' => 'Saint-Louis', 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'TAMBACOUNDA', 'libelle' => 'Tambacounda', 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'THIES', 'libelle' => 'Thiès', 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'ZIGUINCHOR', 'libelle' => 'Ziguinchor', 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $this->command->info('✅ Régions créées');
        }

        // 4c. Départements
        if (!DB::table('departements')->where('code', 'DAKAR_DEP')->exists()) {
            $dakarRegion = DB::table('regions')->where('code', 'DAKAR')->first()?->id;
            $kaolackRegion = DB::table('regions')->where('code', 'KAOLACK')->first()?->id;
            $koldaRegion = DB::table('regions')->where('code', 'KOLDA')->first()?->id;

            if ($dakarRegion && $kaolackRegion && $koldaRegion) {
                DB::table('departements')->insert([
                    ['code' => 'DAKAR_DEP', 'libelle' => 'Dakar', 'region_id' => $dakarRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'GUEDIAWAYE', 'libelle' => 'Guédiawaye', 'region_id' => $dakarRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'RUFISQUE', 'libelle' => 'Rufisque', 'region_id' => $dakarRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'KAOLACK_DEP', 'libelle' => 'Kaolack', 'region_id' => $kaolackRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'KOLDA_DEP', 'libelle' => 'Kolda', 'region_id' => $koldaRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                ]);
                $this->command->info('✅ Départements créés');
            }
        }

        // 4d. Communes
        if (!DB::table('communes')->where('code', 'DAKAR_COM')->exists()) {
            $dakarDep = DB::table('departements')->where('code', 'DAKAR_DEP')->first()?->id;
            $guediawayeDep = DB::table('departements')->where('code', 'GUEDIAWAYE')->first()?->id;
            $rufisqueDep = DB::table('departements')->where('code', 'RUFISQUE')->first()?->id;
            $dakarRegion = DB::table('regions')->where('code', 'DAKAR')->first()?->id;

            if ($dakarDep && $dakarRegion) {
                DB::table('communes')->insert([
                    ['code' => 'DAKAR_COM', 'libelle' => 'Dakar', 'departement_id' => $dakarDep, 'region_id' => $dakarRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'PLATEAU', 'libelle' => 'Plateau', 'departement_id' => $dakarDep, 'region_id' => $dakarRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'GUEDIAWAYE_COM', 'libelle' => 'Guédiawaye', 'departement_id' => $guediawayeDep ?? $dakarDep, 'region_id' => $dakarRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'RUFISQUE_COM', 'libelle' => 'Rufisque', 'departement_id' => $rufisqueDep ?? $dakarDep, 'region_id' => $dakarRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                ]);
                $this->command->info('✅ Communes créées');
            }
        }

        // 4e. Quartiers
        if (!DB::table('quartiers')->where('code', 'MEDINA')->exists()) {
            $dakarCom = DB::table('communes')->where('code', 'DAKAR_COM')->first()?->id;
            $plateauCom = DB::table('communes')->where('code', 'PLATEAU')->first()?->id;
            $guediawayeCom = DB::table('communes')->where('code', 'GUEDIAWAYE_COM')->first()?->id;
            $dakarDep = DB::table('departements')->where('code', 'DAKAR_DEP')->first()?->id;
            $dakarRegion = DB::table('regions')->where('code', 'DAKAR')->first()?->id;

            if ($dakarCom && $dakarDep && $dakarRegion) {
                DB::table('quartiers')->insert([
                    ['code' => 'MEDINA', 'libelle' => 'Médina', 'commune_id' => $dakarCom, 'departement_id' => $dakarDep, 'region_id' => $dakarRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'GRAND_YOFF', 'libelle' => 'Grand Yoff', 'commune_id' => $dakarCom, 'departement_id' => $dakarDep, 'region_id' => $dakarRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'LIBERTE', 'libelle' => 'Liberté', 'commune_id' => $plateauCom ?? $dakarCom, 'departement_id' => $dakarDep, 'region_id' => $dakarRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'SACRE_COEUR', 'libelle' => 'Sacré-Cœur', 'commune_id' => $plateauCom ?? $dakarCom, 'departement_id' => $dakarDep, 'region_id' => $dakarRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['code' => 'GUEDIAWAYE_QUA', 'libelle' => 'Guédiawaye Centre', 'commune_id' => $guediawayeCom ?? $dakarCom, 'departement_id' => $dakarDep, 'region_id' => $dakarRegion, 'pays_id' => $paysId, 'etat' => 'actif', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()],
                ]);
                $this->command->info('✅ Quartiers créés');
            }
        }

        // 5. Unités Organisationnelles
        if (DB::table('unites_organisationnelles')->count() == 0) {
            DB::table('unites_organisationnelles')->insert([
                [
                    'code' => 'ADMIN',
                    'libelle' => 'Administration Générale',
                    'unite_mere_id' => null,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'PEDAGO',
                    'libelle' => 'Unité Pédagogique',
                    'unite_mere_id' => null,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'FINANCE',
                    'libelle' => 'Unité Financière',
                    'unite_mere_id' => null,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'HR',
                    'libelle' => 'Ressources Humaines',
                    'unite_mere_id' => null,
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
            $this->command->info('✅ Unités organisationnelles créées');
        }

        // 5. Année scolaire de base
        if (DB::table('annees_scolaires')->count() == 0) {
            DB::table('annees_scolaires')->insert([
                [
                    'code' => 'AS-2026-2027',
                    'libelle' => '2026-2027',
                    'date_debut' => '2026-09-01',
                    'date_fin' => '2027-08-31',
                    'duree' => 365,
                    'est_courante' => 1,
                    'pays_id' => null,
                    'statut' => 'en_cours',
                    'etat' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
            $this->command->info('✅ Année scolaire créée');
        }

        // 6. Add missing Paramétrage features (must run AFTER TModuleSeeder creates module 23)
        $parametrageModuleId = DB::table('module')->where('libelle', 'Paramétrage')->value('id') ?? 23;

        // Get max ordre for Paramétrage features
        $maxOrdre = DB::table('feature')
            ->where('module_id', $parametrageModuleId)
            ->max('ordre') ?? 0;

        // Add Devises Pays feature if not exists
        if (!DB::table('feature')->where('menu_url', 'devises_pays')->exists()) {
            DB::table('feature')->insert([
                'libelle' => 'Devises Pays',
                'libelle_en' => 'Country Currencies',
                'icone' => 'fas fa-globe',
                'menu_url' => 'devises_pays',
                'module_id' => $parametrageModuleId,
                'ordre' => $maxOrdre + 1,
                'source_system' => 'smilpay',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info('✅ Feature "Devises Pays" ajoutée');
        }

        // Add Types Établissement Spé feature if not exists
        if (!DB::table('feature')->where('menu_url', 'types_etablissement_spe')->exists()) {
            DB::table('feature')->insert([
                'libelle' => 'Types Établissement Spé',
                'libelle_en' => 'Special Institution Types',
                'icone' => 'fas fa-certificate',
                'menu_url' => 'types_etablissement_spe',
                'module_id' => $parametrageModuleId,
                'ordre' => $maxOrdre + 2,
                'source_system' => 'smilpay',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info('✅ Feature "Types Établissement Spé" ajoutée');
        }

        $this->command->info('✅ Seeding de paramétrage terminé!');
    }
}
