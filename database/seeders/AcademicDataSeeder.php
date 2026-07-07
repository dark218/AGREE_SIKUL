<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Institution\Entities\Institution;
use Modules\Campus\Entities\Campus;
use Modules\Ecole\Entities\Ecole;
use Modules\Ecole\Entities\Niveau;
use Modules\Ecole\Entities\Classe;
use Modules\Ecole\Entities\AnneeScolaire;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\GestionApprenants\Entities\Inscription;
use Modules\GestionApprenants\Entities\Tuteur;
use Modules\EnseignantsRH\Entities\Enseignant;
use Modules\CoursHoraires\Entities\Matiere;

class AcademicDataSeeder extends Seeder
{
    public function run(): void
    {
        // Créer année scolaire courante
        $anneeCourante = AnneeScolaire::firstOrCreate(
            ['est_courante' => true],
            [
                'libelle' => date('Y') . '-' . (date('Y') + 1),
                'date_debut' => now()->startOfYear()->addMonths(9),
                'date_fin' => now()->addYear()->startOfYear()->addMonths(8)->endOfMonth(),
                'statut' => 'en_cours',
            ]
        );

        // Créer institutions
        $institutions = Institution::factory(2)->create();

        $institutions->each(function ($institution) use ($anneeCourante) {
            // Créer campuses pour chaque institution
            $campuses = Campus::factory(2)->for($institution)->create();

            $campuses->each(function ($campus) use ($anneeCourante) {
                // Créer écoles pour chaque campus
                $ecoles = Ecole::factory(2)->for($campus)->create();

                $ecoles->each(function ($ecole) use ($anneeCourante) {
                    // Créer niveaux pour chaque école
                    $niveaux = Niveau::factory(3)->for($ecole)->create();

                    // Créer matieres pour l'école
                    $matieres = MatiereUnite::factory(8)->for($ecole)->create();

                    // Créer classes pour chaque niveau
                    $niveaux->each(function ($niveau) use ($ecole) {
                        Classe::factory(2)
                            ->for($ecole)
                            ->for($niveau)
                            ->create()
                            ->each(function ($classe) use ($anneeCourante) {
                                // Créer apprenants pour chaque classe
                                Apprenant::factory(rand(15, 30))->create()
                                    ->each(function ($apprenant) use ($classe, $anneeCourante) {
                                        // Créer inscription
                                        Inscription::factory()
                                            ->for($apprenant)
                                            ->for($classe)
                                            ->for($anneeCourante, 'anneeScolaire')
                                            ->state(['statut' => 'validee'])
                                            ->create();

                                        // Créer tuteur si needed
                                        if (rand(0, 1)) {
                                            Tuteur::factory()
                                                ->for($apprenant)
                                                ->create();
                                        }
                                    });
                            });
                    });
                });
            });
        });

        // Créer enseignants
        Enseignant::factory(20)->create();

        $this->command->info('Academic data seeded successfully!');
    }
}
