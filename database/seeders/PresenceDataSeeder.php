<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Presence\Entities\PresenceSeance;
use Modules\Presence\Entities\AbsenceApprenant;
use Modules\Presence\Entities\JustificatifAbsence;
use Modules\CoursHoraires\Entities\Seance;
use Modules\GestionApprenants\Entities\Apprenant;

class PresenceDataSeeder extends Seeder
{
    public function run(): void
    {
        // Créer seances
        $seances = Seance::factory(20)->create();

        // Créer presences
        $apprenants = Apprenant::all();
        $seances->each(function ($seance) use ($apprenants) {
            $apprenants->random(rand(10, 30))->each(function ($apprenant) use ($seance) {
                PresenceSeance::factory()
                    ->for($apprenant)
                    ->for($seance)
                    ->create();
            });
        });

        // Créer absences apprenants
        AbsenceApprenant::factory(30)->create()
            ->each(function ($absence) {
                if (rand(0, 1)) {
                    JustificatifAbsence::factory()
                        ->for($absence, 'absenceApprenant')
                        ->create();
                }
            });

        $this->command->info('Presence data seeded successfully!');
    }
}
