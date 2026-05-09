<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finances\Entities\TypeFrais;
use Modules\Finances\Entities\Frais;
use Modules\Finances\Entities\Paiement;
use Modules\Finances\Entities\Echeancier;
use Modules\Finances\Entities\Depense;
use Modules\Finances\Entities\ServiceCantine;
use Modules\Finances\Entities\Menu;
use Modules\Finances\Entities\InscriptionCantine;
use Modules\Finances\Entities\PassageCantine;
use Modules\Finances\Entities\ServiceTransport;
use Modules\Finances\Entities\InscriptionTransport;
use Modules\Finances\Entities\ConsultationInfirmerie;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\Ecole\Entities\Ecole;
use Modules\Ecole\Entities\AnneeScolaire;

class FinancesDataSeeder extends Seeder
{
    public function run(): void
    {
        $anneeCourante = AnneeScolaire::where('est_courante', true)->first();
        $ecoles = Ecole::all();
        $apprenants = Apprenant::all();

        // Créer types de frais
        $typesFrais = TypeFrais::factory(6)->create();

        // Créer frais
        $apprenants->each(function ($apprenant) use ($typesFrais, $anneeCourante) {
            $typesFrais->random(rand(2, 4))->each(function ($typeFrais) use ($apprenant, $anneeCourante) {
                $frais = Frais::factory()
                    ->for($apprenant)
                    ->for($typeFrais)
                    ->for($anneeCourante)
                    ->create();

                // Créer paiement si frais en cours de paiement
                if (in_array($frais->statut, ['partiellement_paye', 'paye'])) {
                    Paiement::factory()
                        ->for($frais)
                        ->create(['montant' => min($frais->montant, $frais->montant_paye)]);
                }
            });
        });

        // Créer écheanciers
        $typesFrais->each(function ($typeFrais) {
            Echeancier::factory(3)->for($typeFrais)->create();
        });

        // Créer dépenses
        Depense::factory(20)->create();

        // Services de cantine
        $ecoles->each(function ($ecole) use ($apprenants, $anneeCourante) {
            $cantine = ServiceCantine::factory()->for($ecole)->create();

            // Menus cantine
            Menu::factory(15)->for($cantine)->create();

            // Inscriptions cantine
            $apprenants->random(rand(10, 30))->each(function ($apprenant) use ($cantine, $anneeCourante) {
                $inscriptionCantine = InscriptionCantine::factory()
                    ->for($apprenant)
                    ->for($cantine)
                    ->for($anneeCourante)
                    ->create();

                // Passages cantine
                PassageCantine::factory(rand(10, 20))
                    ->for($apprenant)
                    ->for($cantine)
                    ->create();
            });
        });

        // Services de transport
        $ecoles->each(function ($ecole) use ($apprenants, $anneeCourante) {
            $transport = ServiceTransport::factory()->for($ecole)->create();

            // Inscriptions transport
            $apprenants->random(rand(5, 20))->each(function ($apprenant) use ($transport, $anneeCourante) {
                InscriptionTransport::factory()
                    ->for($apprenant)
                    ->for($transport)
                    ->for($anneeCourante)
                    ->create();
            });
        });

        // Consultations infirmerie
        ConsultationInfirmerie::factory(25)->create();

        $this->command->info('Finances data seeded successfully!');
    }
}
