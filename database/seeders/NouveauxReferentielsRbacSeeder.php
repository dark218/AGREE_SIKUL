<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Enregistre dans le RBAC les 10 nouveaux référentiels Paramétrage :
 *   - Genre (déjà créé mais on force ici pour idempotence)
 *   - TypeContrat, StatutEmploye, SituationMatrimoniale,
 *     LienParente, Civilite, StatutApprenant, TypeInscription,
 *     GroupeSanguin, Langue
 *
 * Actions :
 *  1. Insère une `feature` par module (module_id = Paramétrage)
 *  2. Crée 4 permissions par feature : {menu_url}-list/create/update/delete
 *  3. Attache toutes les permissions au rôle `super_admin` (et `admin` si présent)
 *
 * Idempotent : ne re-crée rien si déjà présent.
 *
 * Usage : php artisan db:seed --class=NouveauxReferentielsRbacSeeder
 */
class NouveauxReferentielsRbacSeeder extends Seeder
{
    public function run(): void
    {
        $parametrageModuleId = DB::table('module')
            ->where('libelle', 'Paramétrage')
            ->value('id') ?? 23;

        $maxOrdre = DB::table('feature')
            ->where('module_id', $parametrageModuleId)
            ->max('ordre') ?? 0;

        // menu_url | libelle | libelle_en | icone
        $features = [
            'genres'                    => ['Genres',                 'Genders',            'fas fa-venus-mars'],
            'civilites'                 => ['Civilités',              'Titles',             'fas fa-id-badge'],
            'liens_parente'             => ['Liens de parenté',       'Kinship links',      'fas fa-heart'],
            'situations_matrimoniales'  => ['Situations matrimoniales','Marital status',    'fas fa-ring'],
            'groupes_sanguins'          => ['Groupes sanguins',       'Blood groups',       'fas fa-tint'],
            'langues'                   => ['Langues',                'Languages',          'fas fa-language'],
            'types_contrats'            => ['Types de contrat',       'Contract types',     'fas fa-file-signature'],
            'statuts_employes'          => ['Statuts employé',        'Employee statuses',  'fas fa-user-check'],
            'statuts_apprenants'        => ['Statuts apprenant',      'Student statuses',   'fas fa-user-graduate'],
            'types_inscriptions'        => ["Types d'inscription",    'Enrollment types',   'fas fa-clipboard-list'],
        ];

        $createdFeatures = 0;
        $createdPermissions = 0;

        foreach ($features as $menuUrl => [$libelle, $libelleEn, $icone]) {
            // 1. Feature
            $featureId = DB::table('feature')->where('menu_url', $menuUrl)->value('id');
            if (!$featureId) {
                $maxOrdre++;
                $featureId = DB::table('feature')->insertGetId([
                    'libelle'       => $libelle,
                    'libelle_en'    => $libelleEn,
                    'icone'         => $icone,
                    'menu_url'      => $menuUrl,
                    'module_id'     => $parametrageModuleId,
                    'ordre'         => $maxOrdre,
                    'source_system' => 'agree_sikul',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $createdFeatures++;
                $this->command->info("✅ Feature '$libelle' créée (id=$featureId)");
            }

            // 2. 4 Permissions par feature
            foreach (['list', 'create', 'update', 'delete'] as $action) {
                $permission = Permission::firstOrCreate(
                    ['name' => $menuUrl . '-' . $action],
                    [
                        'libelle'    => ucfirst($action) . ' - ' . $libelle,
                        'guard_name' => 'web',
                        'feature_id' => $featureId,
                    ]
                );
                if ($permission->wasRecentlyCreated) {
                    $createdPermissions++;
                }
                // Réparation : lier au feature si manquant
                if (!$permission->feature_id) {
                    $permission->update(['feature_id' => $featureId]);
                }
            }
        }

        // 3. Attribuer TOUTES les nouvelles permissions aux rôles admin
        $allNewPermissions = [];
        foreach (array_keys($features) as $url) {
            foreach (['list', 'create', 'update', 'delete'] as $a) {
                $allNewPermissions[] = $url . '-' . $a;
            }
        }

        foreach (['super_admin', 'superadmin', 'admin', 'directeur_general'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($allNewPermissions);
                $this->command->info("✅ Permissions attribuées au rôle '$roleName'");
            }
        }

        $this->command->info("🎉 Terminé : $createdFeatures features + $createdPermissions permissions créées");
    }
}
