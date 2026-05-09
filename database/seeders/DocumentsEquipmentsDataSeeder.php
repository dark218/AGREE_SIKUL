<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Documents\Entities\CategorieDocument;
use Modules\Documents\Entities\Document;
use Modules\Documents\Entities\ModeleRapport;
use Modules\Documents\Entities\Rapport;
use Modules\Inventaire\Entities\CategorieEquipement;
use Modules\Inventaire\Entities\Equipement;
use Modules\Inventaire\Entities\MaintenanceEquipement;
use Modules\Ecole\Entities\Ecole;
use App\Models\User;

class DocumentsEquipmentsDataSeeder extends Seeder
{
    public function run(): void
    {
        $ecoles = Ecole::all();
        $users = User::all();

        if ($users->count() < 2) {
            $users = User::factory(10)->create();
        }

        // Catégories de documents
        $categoriesDoc = CategorieDocument::factory(6)->create();

        // Documents
        $categoriesDoc->each(function ($categorie) use ($users) {
            Document::factory(5)
                ->for($categorie)
                ->create();
        });

        // Modèles de rapports
        $modeles = ModeleRapport::factory(5)->create();

        // Rapports
        $modeles->each(function ($modele) use ($users) {
            Rapport::factory(3)
                ->for($modele)
                ->create();
        });

        // Catégories d'équipements
        $categoriesEqu = CategorieEquipement::factory(6)->create();

        // Équipements
        $ecoles->each(function ($ecole) use ($categoriesEqu) {
            $categoriesEqu->each(function ($categorie) use ($ecole) {
                Equipement::factory(rand(3, 5))
                    ->for($categorie)
                    ->for($ecole)
                    ->create()
                    ->each(function ($equipement) {
                        // Créer maintenances
                        if (rand(0, 1)) {
                            MaintenanceEquipement::factory(rand(1, 3))
                                ->for($equipement)
                                ->create();
                        }
                    });
            });
        });

        $this->command->info('Documents and Equipments data seeded successfully!');
    }
}
