<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Systèmes de base
            TModuleSeeder::class,
            TFeatureSeeder::class,

            // Permissions et Rôles RBAC - Accord Sikul Scolaire
            PermissionSeeder::class,
            RoleSeeder::class,
            AcademicPermissionsSeeder::class, // Permissions "activate" pour les 20 entités académiques
            FixPermissionsSeeder::class, // Toutes les permissions correctes pour Academique et Finances
            CreateAllPermissionsSeeder::class, // Scanne le codebase et crée toutes les permissions `permission.check:*` réellement utilisées
            // RbacPermissionsSeeder::class, // Déjà inclus dans PermissionSeeder et RoleSeeder

            // Fonctionnalités additionnelles (features menu + permissions + assignation aux rôles)
            ListesManuelsFeatureSeeder::class,   // Liste des manuels et fournitures
            BibliothequeFeaturesSeeder::class,   // Bibliothèque : structures, entrées, sorties, inventaire

            // §10.7 : référentiels du module RessourcesLogistique (catégories
            // Équipements/Documents/Fournitures + bibliothèque par défaut).
            // Sans ce seed, les dropdowns "Catégorie" sont vides et la création
            // d'équipement/document/fourniture est bloquée.
            RessourcesLogistiqueReferentielsSeeder::class,

            // Utilisateurs de base
            AgreeSikulUsersSeeder::class,

            // Données de paramétrage de base
            ParametrageDataSeeder::class,
            DevisesSeeder::class,
            SalleSeeder::class,

            // Matrice permissions par rôle
            RolePermissionMatrixSeeder::class,

            // Données de test pour formulaires
            TestDataSeeder::class,

            // Données académiques (Commenté - factories manquantes)
            // AcademicDataSeeder::class,
            // DevoirsDataSeeder::class,
            // ExamsNotesDataSeeder::class,
            // PresenceDataSeeder::class,
            // FinancesDataSeeder::class,
            // BibliothequeDataSeeder::class,
            // DocumentsEquipmentsDataSeeder::class,
            // CommunicationDataSeeder::class,
        ]);
    }
}
