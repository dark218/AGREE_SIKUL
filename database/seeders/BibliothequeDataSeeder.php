<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Bibliotheque\Entities\Bibliotheque;
use Modules\Bibliotheque\Entities\Ouvrage;
use Modules\Bibliotheque\Entities\Exemplaire;
use Modules\Bibliotheque\Entities\Emprunt;
use Modules\Bibliotheque\Entities\Reservation;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\Ecole\Entities\Ecole;

class BibliothequeDataSeeder extends Seeder
{
    public function run(): void
    {
        $ecoles = Ecole::all();
        $apprenants = Apprenant::all();

        $ecoles->each(function ($ecole) use ($apprenants) {
            // Créer bibliothèque
            $bibliotheque = Bibliotheque::factory()->for($ecole)->create();

            // Créer ouvrages
            $ouvrages = Ouvrage::factory(30)->create();

            // Créer exemplaires
            $ouvrages->each(function ($ouvrage) use ($bibliotheque) {
                Exemplaire::factory(rand(1, 3))
                    ->for($ouvrage)
                    ->for($bibliotheque)
                    ->create();
            });

            // Créer emprunts
            $exemplaires = Exemplaire::where('bibliotheque_id', $bibliotheque->id)->get();
            $apprenants->random(rand(20, 40))->each(function ($apprenant) use ($exemplaires) {
                $exemplaires->random(rand(1, 3))->each(function ($exemplaire) use ($apprenant) {
                    Emprunt::factory()
                        ->for($apprenant)
                        ->for($exemplaire)
                        ->create();
                });
            });

            // Créer réservations
            $apprenants->random(rand(10, 20))->each(function ($apprenant) use ($ouvrages) {
                Reservation::factory()
                    ->for($apprenant)
                    ->for($ouvrages->random())
                    ->create();
            });
        });

        $this->command->info('Library data seeded successfully!');
    }
}
