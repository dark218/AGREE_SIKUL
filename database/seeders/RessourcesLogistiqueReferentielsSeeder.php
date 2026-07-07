<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * §10.7 : peuple les référentiels du module RessourcesLogistique.
 *
 * Sans ces données, les dropdowns de :
 *   - /equipements/create      → "Catégorie" vide
 *   - /documents/create        → "Catégorie" vide
 *   - /fournitures/create      → "Catégorie" vide
 *   - /ouvrages/create         → "Bibliothèque" vide
 * ...ce qui bloque la création de tout item RL.
 *
 * Idempotent : chaque insert est protégé par insertOrIgnore et un check
 * de présence de la table (Schema::hasTable).
 */
class RessourcesLogistiqueReferentielsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $ecoleId = DB::table('ecoles')->value('id');

        // ---------- Catégories Équipements ----------
        if (Schema::hasTable('categories_equipements')) {
            $categoriesEquipements = [
                ['libelle' => 'Mobilier',       'description' => 'Tables, chaises, armoires, étagères'],
                ['libelle' => 'Informatique',   'description' => 'Ordinateurs, imprimantes, projecteurs'],
                ['libelle' => 'Audiovisuel',    'description' => 'TV, vidéoprojecteurs, enceintes, micros'],
                ['libelle' => 'Sport',          'description' => 'Matériel de sport, ballons, tenues'],
                ['libelle' => 'Laboratoire',    'description' => 'Instruments de mesure, verrerie, chimie'],
                ['libelle' => 'Nettoyage',      'description' => 'Balais, seaux, produits d\'entretien'],
                ['libelle' => 'Cuisine',        'description' => 'Ustensiles, électroménager, gazinière'],
                ['libelle' => 'Sécurité',       'description' => 'Extincteurs, alarmes, caméras'],
                ['libelle' => 'Électroménager', 'description' => 'Réfrigérateurs, climatiseurs, chauffage'],
                ['libelle' => 'Médical',        'description' => 'Trousse de secours, matériel infirmerie'],
            ];
            foreach ($categoriesEquipements as $cat) {
                DB::table('categories_equipements')->insertOrIgnore(array_merge($cat, [
                    'source_system' => 'agree_sikul',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]));
            }
            $this->command?->info('✅ Catégories équipements créées');
        }

        // ---------- Catégories Documents ----------
        if (Schema::hasTable('categories_documents')) {
            $categoriesDocuments = [
                ['libelle' => 'Administratif', 'description' => 'Documents administratifs, courriers'],
                ['libelle' => 'Pédagogique',   'description' => 'Programmes, cours, supports'],
                ['libelle' => 'Financier',     'description' => 'Bilans, factures, budgets'],
                ['libelle' => 'Ressources humaines', 'description' => 'Contrats, fiches de paie, congés'],
                ['libelle' => 'Juridique',     'description' => 'Statuts, conventions, contrats'],
                ['libelle' => 'Communication', 'description' => 'Affiches, brochures, newsletters'],
                ['libelle' => 'Archives',      'description' => 'Documents anciens conservés'],
                ['libelle' => 'Rapports',      'description' => 'Rapports d\'activité, audits'],
            ];
            foreach ($categoriesDocuments as $cat) {
                DB::table('categories_documents')->insertOrIgnore(array_merge($cat, [
                    'source_system' => 'agree_sikul',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]));
            }
            $this->command?->info('✅ Catégories documents créées');
        }

        // ---------- Catégories Fournitures ----------
        if (Schema::hasTable('categories_fournitures')) {
            $categoriesFournitures = [
                ['libelle' => 'Papeterie',       'description' => 'Stylos, cahiers, feuilles, agrafeuses'],
                ['libelle' => 'Fournitures scolaires', 'description' => 'Craies, tableaux, éponges'],
                ['libelle' => 'Consommables IT', 'description' => 'Cartouches, câbles, souris'],
                ['libelle' => 'Hygiène',         'description' => 'Savon, papier toilette, gel hydroalcoolique'],
                ['libelle' => 'Cuisine',         'description' => 'Vaisselle jetable, essuie-tout'],
                ['libelle' => 'Divers',          'description' => 'Autres consommables'],
            ];
            foreach ($categoriesFournitures as $cat) {
                DB::table('categories_fournitures')->insertOrIgnore(array_merge($cat, [
                    'source_system' => 'agree_sikul',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]));
            }
            $this->command?->info('✅ Catégories fournitures créées');
        }

        // ---------- Bibliothèque par défaut ----------
        if (Schema::hasTable('bibliotheques') && DB::table('bibliotheques')->count() === 0) {
            $row = [
                'ecole_id' => $ecoleId,
                'nom'      => 'Bibliothèque principale',
                'adresse'  => 'Bâtiment principal',
                'capacite' => 500,
                'etat'     => 'actif',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            // Ajout conditionnel de source_system si la colonne existe (schéma évolutif).
            if (Schema::hasColumn('bibliotheques', 'source_system')) {
                $row['source_system'] = 'agree_sikul';
            }
            DB::table('bibliotheques')->insert($row);
            $this->command?->info('✅ Bibliothèque par défaut créée');
        }
    }
}
