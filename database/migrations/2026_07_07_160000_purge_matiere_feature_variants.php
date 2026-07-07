<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * §10 renforcement — Purge globale de toutes les features de menu retirées
 * lors des refactos Phase 1 à Phase 3.
 *
 * Contexte : plusieurs migrations spécifiques purgent leur propre feature
 * (matieres, absences_apprenants, justificatifs, bibliothèque, manuels...).
 * En prod on retrouve toutefois des variantes de menu_url ou libellé qui
 * n'étaient pas dans la liste initiale — d'où le "menu Matière" qui
 * réapparaissait dans le sidebar.
 *
 * Cette migration liste EXHAUSTIVEMENT toutes les variantes retirées et les
 * purge en une passe idempotente (peut être rejouée sans effet).
 *
 * Purges :
 *   - feature dont menu_url ∈ liste ci-dessous
 *   - feature dont libelle exact ∈ liste ci-dessous (belt-and-suspenders)
 *   - permissions Spatie liées (via feature_id historique + naming {slug}-*)
 *   - role_has_permissions + model_has_permissions (orphelins)
 *
 * NB : les tables physiques (matieres, absences_apprenants, etc.) sont déjà
 * droppées par leurs migrations dédiées — ici on ne touche QUE au menu/RBAC.
 */
return new class extends Migration
{
    /**
     * §10.5 : URLs retirées (fallback DEFAULT_MENU_CONFIG dans TheSidebar.vue).
     * Chaque entrée est une VARIANTE potentielle du même menu (URL évolutive,
     * kebab / snake / pluriel / singulier).
     */
    private const RETIRED_MENU_URLS = [
        // Matières (table `matieres` droppée — remplacée par MatiereUnite)
        'matieres', 'matiere',
        // Absences Apprenants (Presence est source unique)
        'absences-apprenants', 'absences_apprenants', 'absence-apprenants', 'absence_apprenants',
        // Justificatifs Absences (redondant avec justificatif_path)
        'justificatifs-absences', 'justificatifs_absences', 'justificatifs', 'justificatif-absences',
        // Niveaux (doublon NiveauEtude — canonique = niveaux-etudes)
        'niveaux', 'niveau',
        // Catégorie Apprenant (doublon TypeApprenant + StatutApprenant)
        'categorie-apprenants', 'categorie_apprenants', 'categories-apprenants', 'categories_apprenants',
        // Zones (référentiel dormant)
        'zones', 'zone', 'kpi-zones', 'kpi_zones',
        // Civilités (fusion → titres-civilites)
        'civilites', 'civilite',
        // Langues (dormant, stockage JSON dans enseignants.languages)
        'langues', 'langue',
        // Types de contrat (fusion → natures-contrat)
        'types-contrats', 'types_contrats', 'type-contrat', 'type_contrat',
        // Menus canteen (doublon MenuCantine — canonique = services-cantine)
        'menus', 'menu',
        // Bibliothèque acad (recréée sous RessourcesLogistique — schéma différent)
        'bibliotheque', 'bibliotheques',
        'catalogue-livres', 'catalogue_livres',
        'entrees-livres', 'entrees_livres',
        'sorties-livres', 'sorties_livres',
        'inventaire-livres', 'inventaire_livres', 'inventaires-livres',
        // Manuels standalone (fusionné dans ListeManuels)
        'manuels', 'manuel', 'livres-manuels',
        // Services doublons singulier (Phase 4.6c — canoniques pluriel)
        'passages-cantine', 'passages_cantine',
        'inscriptions-cantine', 'inscriptions_cantine',
        'inscriptions-transport', 'inscriptions_transport',
        'consultations-infirmerie', 'consultations_infirmerie',
        // Types Établissements Spé (fusion)
        'types-etablissements-spe', 'types_etablissements_spe',
    ];

    /**
     * Libellés exacts à cibler dans le module Académique (id 25) uniquement,
     * pour ne pas dégommer des features homonymes d'autres modules.
     */
    private const RETIRED_ACADEMIQUE_LIBELLES = [
        'Matière', 'Matières',
        'Absences Apprenants', 'Absence Apprenants', 'Absence Apprenant',
        'Justificatifs Absences', 'Justificatifs',
        'Niveau', 'Niveaux',
        'Catégorie Apprenant', 'Catégories Apprenants',
        'Langues', 'Civilités', 'Types de contrat',
        'Manuels', 'Menu',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('feature')) {
            return;
        }

        // 1. Sélectionne les features retirées (URLs + libellés Académique).
        $featureIds = DB::table('feature')
            ->where(function ($q) {
                $q->whereIn('menu_url', self::RETIRED_MENU_URLS)
                  ->orWhere(function ($qq) {
                      $qq->where('module_id', 25) // Académique
                         ->whereIn('libelle', self::RETIRED_ACADEMIQUE_LIBELLES);
                  });
            })
            ->pluck('id')
            ->all();

        if (empty($featureIds)) {
            return;
        }

        // 2. Coupe les permissions Spatie liées (feature_id historique + naming).
        if (Schema::hasTable('permissions')) {
            $permIds = Schema::hasColumn('permissions', 'feature_id')
                ? DB::table('permissions')->whereIn('feature_id', $featureIds)->pluck('id')->all()
                : [];

            foreach (self::RETIRED_MENU_URLS as $slug) {
                $ids = DB::table('permissions')
                    ->where('name', 'like', $slug . '-%')
                    ->pluck('id')->all();
                $permIds = array_merge($permIds, $ids);
            }
            $permIds = array_values(array_unique($permIds));

            if (!empty($permIds)) {
                if (Schema::hasTable('role_has_permissions')) {
                    DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
                }
                if (Schema::hasTable('model_has_permissions')) {
                    DB::table('model_has_permissions')->whereIn('permission_id', $permIds)->delete();
                }
                DB::table('permissions')->whereIn('id', $permIds)->delete();
            }
        }

        // 3. Purge les features (les tables physiques sont déjà droppées par
        //    les migrations spécifiques, donc pas de risque d'orphelin).
        DB::table('feature')->whereIn('id', $featureIds)->delete();
    }

    public function down(): void
    {
        // Aucune restauration — ces menus ne doivent jamais réapparaître.
        // Si vraiment nécessaire (revert), utiliser TFeatureSeeder de la
        // révision précédente.
    }
};
