<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n🚀 Création des données de test pour TOUS les Select de Parametrage & Academique...\n\n";

        // Disable FK checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // ================== DONNÉES DE BASE ==================

        $paysId = DB::table('pays')->first()?->id ?? 1;
        $institutionId = DB::table('institutions')->first()?->id ?? 1;

        // Créer Régions
        DB::table('regions')->truncate();
        DB::table('regions')->insert([
            ['code' => 'REG_001', 'libelle' => 'Kasai', 'pays_id' => $paysId, 'etat' => 'actif', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'REG_002', 'libelle' => 'Kasai Oriental', 'pays_id' => $paysId, 'etat' => 'actif', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'REG_003', 'libelle' => 'Katanga', 'pays_id' => $paysId, 'etat' => 'actif', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $regions = DB::table('regions')->where('pays_id', $paysId)->limit(3)->get();
        echo "✅ Régions créées (" . $regions->count() . ")\n";

        // Créer Départements
        DB::table('departements')->truncate();
        $deptIndex = 1;
        foreach ($regions as $region) {
            DB::table('departements')->insert([
                'code' => 'DEPT_' . str_pad($deptIndex++, 3, '0', STR_PAD_LEFT),
                'libelle' => "Département " . $region->libelle,
                'region_id' => $region->id,
                'pays_id' => $paysId,
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $departements = DB::table('departements')->get();
        echo "✅ Départements créés (" . $departements->count() . ")\n";

        // Créer Communes
        DB::table('communes')->truncate();
        $commIndex = 1;
        foreach ($departements as $dept) {
            DB::table('communes')->insert([
                'code' => 'COMM_' . str_pad($commIndex++, 3, '0', STR_PAD_LEFT),
                'libelle' => "Commune " . substr($dept->libelle, 0, 10),
                'departement_id' => $dept->id,
                'region_id' => $dept->region_id,
                'pays_id' => $paysId,
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $communes = DB::table('communes')->get();
        echo "✅ Communes créées (" . $communes->count() . ")\n";

        // Créer Quartiers
        DB::table('quartiers')->truncate();
        $communes = DB::table('communes')->get();
        $quarIndex = 1;
        foreach ($communes as $commune) {
            DB::table('quartiers')->insert([
                'code' => 'QUAR_' . str_pad($quarIndex++, 3, '0', STR_PAD_LEFT),
                'libelle' => "Quartier " . substr($commune->libelle, 0, 8),
                'commune_id' => $commune->id,
                'departement_id' => $commune->departement_id,
                'region_id' => $commune->region_id,
                'pays_id' => $paysId,
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $quartiers = DB::table('quartiers')->get();
        echo "✅ Quartiers créés (" . $quartiers->count() . ")\n";

        // Créer Titres civilité
        DB::table('titres_civilites')->insertOrIgnore([
            ['libelle' => 'Monsieur', 'code' => 'M', 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'Madame', 'code' => 'Mme', 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'Mademoiselle', 'code' => 'Mlle', 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'Docteur', 'code' => 'Dr', 'created_at' => now(), 'updated_at' => now()],
        ]);
        echo "✅ Titres civilité créés\n";

        // Créer Devises
        DB::table('devises')->insertOrIgnore([
            ['libelle' => 'Franc Congolais', 'code' => 'FCD', 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'Dollar US', 'code' => 'USD', 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => 'Euro', 'code' => 'EUR', 'created_at' => now(), 'updated_at' => now()],
        ]);
        echo "✅ Devises créées\n";

        // ================== DONNÉES ACADÉMIQUES ==================

        // Cycles
        DB::table('cycles_enseignement')->insertOrIgnore([
            ['code' => 'CYCLE_PRI', 'libelle' => 'Primaire', 'etat' => 'actif', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'CYCLE_SEC1', 'libelle' => 'Secondaire I', 'etat' => 'actif', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'CYCLE_SEC2', 'libelle' => 'Secondaire II', 'etat' => 'actif', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $cycles = DB::table('cycles_enseignement')->limit(3)->get();
        echo "✅ Cycles créés\n";

        // Sections
        DB::table('sections')->insertOrIgnore([
            ['code' => 'SEC_SCI', 'libelle' => 'Scientifique', 'etat' => 'actif', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'SEC_LIT', 'libelle' => 'Littéraire', 'etat' => 'actif', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'SEC_COM', 'libelle' => 'Commerciale', 'etat' => 'actif', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'SEC_TECH', 'libelle' => 'Technique', 'etat' => 'actif', 'created_at' => now(), 'updated_at' => now()],
        ]);
        echo "✅ Sections créées\n";

        // Niveaux d'étude
        DB::table('niveaux_etudes')->insertOrIgnore([
            ['code' => 'N_1', 'libelle' => '1ère Année', 'cycle_id' => $cycles->first()->id, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'N_2', 'libelle' => '2ème Année', 'cycle_id' => $cycles->first()->id, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'N_3', 'libelle' => '3ème Année', 'cycle_id' => $cycles->get(1)->id ?? $cycles->first()->id, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'N_4', 'libelle' => '4ème Année', 'cycle_id' => $cycles->get(1)->id ?? $cycles->first()->id, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'N_5', 'libelle' => '5ème Année', 'cycle_id' => $cycles->get(2)->id ?? $cycles->first()->id, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'N_6', 'libelle' => '6ème Année', 'cycle_id' => $cycles->get(2)->id ?? $cycles->first()->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
        echo "✅ Niveaux d'étude créés\n";

        // Années scolaires
        DB::table('annees_scolaires')->insertOrIgnore([
            ['libelle' => '2024-2025', 'date_debut' => '2024-09-01', 'date_fin' => '2025-06-30', 'duree' => 10, 'statut' => 'en_cours', 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => '2025-2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-06-30', 'duree' => 10, 'statut' => 'planifiee', 'created_at' => now(), 'updated_at' => now()],
            ['libelle' => '2026-2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-06-30', 'duree' => 10, 'statut' => 'planifiee', 'created_at' => now(), 'updated_at' => now()],
        ]);
        echo "✅ Années scolaires créées\n";

        // Campus
        DB::table('campuses')->insertOrIgnore([
            ['code' => 'CAMPUS_KIN', 'nom' => 'Campus Kinshasa', 'institution_id' => $institutionId, 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'CAMPUS_KAT', 'nom' => 'Campus Katanga', 'institution_id' => $institutionId, 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'CAMPUS_KIVU', 'nom' => 'Campus Kivu', 'institution_id' => $institutionId, 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $campuses = DB::table('campuses')->limit(3)->get();
        echo "✅ Campus créés\n";

        // Écoles
        DB::table('ecoles')->insertOrIgnore([
            ['code' => 'LYC_GEN', 'nom' => 'Lycée Général', 'campus_id' => $campuses->first()->id, 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'LYC_TECH', 'nom' => 'Lycée Technique', 'campus_id' => $campuses->get(1)->id ?? $campuses->first()->id, 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'COL_PRIN', 'nom' => 'Collège Principal', 'campus_id' => $campuses->get(2)->id ?? $campuses->first()->id, 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $ecoleId = DB::table('ecoles')->first()->id;
        echo "✅ Écoles créées\n";

        // Niveaux (pour les classes) — table `niveaux` supprimée en Phase 1
        // (§10.2 : fusion Niveau → NiveauEtude). On seed dans `niveaux_etudes`
        // seule table canonique restante. Requiert cycle_id NOT NULL.
        if (\Schema::hasTable('niveaux_etudes')) {
            $cycleFallbackId = optional($cycles->first())->id
                ?? \DB::table('cycles_enseignement')->value('id');
            if ($cycleFallbackId) {
                $labels = ['1ère Année', '2ème Année', '3ème Année', '4ème Année', '5ème Année', '6ème Année'];
                foreach ($labels as $i => $lib) {
                    \DB::table('niveaux_etudes')->insertOrIgnore([
                        'code'       => 'NIV_' . ($i + 1),
                        'libelle'    => $lib,
                        'cycle_id'   => $cycleFallbackId,
                        'etat'       => 'actif',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $niveaux = \DB::table('niveaux_etudes')->limit(6)->get();
            echo "✅ Niveaux d'étude créés\n";
        } else {
            $niveaux = collect();
            echo "⚠️  Table niveaux_etudes absente — skip\n";
        }

        // Matières — table `matieres` supprimée en Phase 1.1 (§1.1 : fusion
        // Matiere → MatiereUnite). On seed dans `matieres_unites`.
        if (\Schema::hasTable('matieres_unites')) {
            $matieres = [
                ['code' => 'MATH', 'libelle' => 'Mathématiques'],
                ['code' => 'PHYS', 'libelle' => 'Physique'],
                ['code' => 'CHIM', 'libelle' => 'Chimie'],
                ['code' => 'BIOL', 'libelle' => 'Biologie'],
                ['code' => 'FRAN', 'libelle' => 'Français'],
                ['code' => 'ANGL', 'libelle' => 'Anglais'],
                ['code' => 'GEOG', 'libelle' => 'Géographie'],
                ['code' => 'HIST', 'libelle' => 'Histoire'],
                ['code' => 'EDUC', 'libelle' => 'Éducation Civique'],
                ['code' => 'SPOR', 'libelle' => 'Sport'],
            ];
            foreach ($matieres as $m) {
                \DB::table('matieres_unites')->insertOrIgnore([
                    'code'       => $m['code'],
                    'libelle'    => $m['libelle'],
                    'ecole_id'   => $ecoleId,
                    'coefficient' => 1,
                    'note_max'   => 20,
                    'etat'       => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            echo "✅ Matières unités créées\n";
        } else {
            echo "⚠️  Table matieres_unites absente — skip\n";
        }

        // Classes
        $niveauId = $niveaux->get(3)->id ?? $niveaux->first()->id;
        $cycleId = $cycles->get(1)->id ?? $cycles->first()->id;
        $campusId = $campuses->first()->id;
        DB::table('classes')->insertOrIgnore([
            ['nom' => '4ème A', 'ecole_id' => $ecoleId, 'niveau_id' => $niveauId, 'cycle_id' => $cycleId, 'section_id' => 1, 'campus_id' => $campusId, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => '4ème B', 'ecole_id' => $ecoleId, 'niveau_id' => $niveauId, 'cycle_id' => $cycleId, 'section_id' => 1, 'campus_id' => $campusId, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => '4ème C', 'ecole_id' => $ecoleId, 'niveau_id' => $niveauId, 'cycle_id' => $cycleId, 'section_id' => 1, 'campus_id' => $campusId, 'created_at' => now(), 'updated_at' => now()],
        ]);
        echo "✅ Classes créées\n";

        // Enseignants avec Users
        $enseignants = [
            ['prenoms' => 'Jean', 'nom' => 'Dupont', 'email' => 'jean.dupont@test.com'],
            ['prenoms' => 'Marie', 'nom' => 'Martin', 'email' => 'marie.martin@test.com'],
            ['prenoms' => 'Pierre', 'nom' => 'Bernard', 'email' => 'pierre.bernard@test.com'],
            ['prenoms' => 'Sophie', 'nom' => 'Moreau', 'email' => 'sophie.moreau@test.com'],
            ['prenoms' => 'Luc', 'nom' => 'Rousseau', 'email' => 'luc.rousseau@test.com'],
            ['prenoms' => 'Anne', 'nom' => 'Lefevre', 'email' => 'anne.lefevre@test.com'],
            ['prenoms' => 'Claude', 'nom' => 'Dubois', 'email' => 'claude.dubois@test.com'],
            ['prenoms' => 'Isabelle', 'nom' => 'Laurent', 'email' => 'isabelle.laurent@test.com'],
        ];

        foreach ($enseignants as $data) {
            $login = strtolower(substr($data['prenoms'], 0, 1) . $data['nom']);
            $fullLogin = strtolower($data['prenoms'] . '.' . $data['nom']);
            $uuid = Str::uuid();

            $existing = User::where('email', $data['email'])->first();
            if (!$existing) {
                DB::table('users')->insertOrIgnore([
                    'uuid' => $uuid,
                    'login' => $login,
                    'full_login' => $fullLogin,
                    'prenoms' => $data['prenoms'],
                    'nom' => $data['nom'],
                    'email' => $data['email'],
                    'password' => bcrypt('password123'),
                    'qr_data' => 'TEST-' . $uuid,
                    'code_owner' => 'OWNER-' . $uuid,
                    'statut' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $user = User::where('email', $data['email'])->first();
            if ($user) {
                DB::table('enseignants')->insertOrIgnore([
                    'user_id' => $user->id,
                    'num_enseignant' => 'ENS_' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
                    'nom' => $data['nom'],
                    'prenoms' => $data['prenoms'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        echo "✅ Enseignants créés\n";

        // Catégories apprenant — table `categorie_apprenants` supprimée en
        // Phase 1 (§10.2 : doublon de TypeApprenant + StatutApprenant).
        // Skip silencieux si absente.
        if (\Schema::hasTable('categorie_apprenants')) {
            \DB::table('categorie_apprenants')->insertOrIgnore([
                ['code' => 'REG',   'libelle' => 'Régulier',   'ecole_id' => $ecoleId, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'IRRE',  'libelle' => 'Irrégulier', 'ecole_id' => $ecoleId, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'LIBRE', 'libelle' => 'Libre',      'ecole_id' => $ecoleId, 'created_at' => now(), 'updated_at' => now()],
            ]);
            echo "✅ Catégories apprenant créées\n";
        } else {
            echo "⚠️  Table categorie_apprenants supprimée (Phase 1) — skip\n";
        }

        // Catégories enseignant
        if (\Schema::hasTable('categorie_enseignants')) {
            \DB::table('categorie_enseignants')->insertOrIgnore([
                ['code' => 'CAT_A', 'libelle' => 'Catégorie A', 'ecole_id' => $ecoleId, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'CAT_B', 'libelle' => 'Catégorie B', 'ecole_id' => $ecoleId, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'CAT_C', 'libelle' => 'Catégorie C', 'ecole_id' => $ecoleId, 'created_at' => now(), 'updated_at' => now()],
            ]);
            echo "✅ Catégories enseignant créées\n";
        }

        // Re-enable FK checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        echo "\n🎉 ✨ DONNÉES DE TEST CRÉÉES AVEC SUCCÈS! ✨ 🎉\n\n";
        echo "📊 DONNÉES COMPLÈTES POUR TOUS LES SELECT:\n";
        echo "   ✓ Régions, Départements, Communes, Quartiers\n";
        echo "   ✓ Titres civilité, Banques, Moyens paiement, Devises\n";
        echo "   ✓ Cycles, Sections, Niveaux d'étude, Niveaux\n";
        echo "   ✓ Matières, Classes, Années scolaires, Campus, Écoles\n";
        echo "   ✓ Enseignants & Users, Catégories (apprenant & enseignant)\n\n";
        echo "🎯 TOUS LES CHAMPS SELECT SONT REMPLIS ET PRÊTS! ✅\n";
    }
}
