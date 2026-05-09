<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Devoirs\Entities\Devoir;
use Modules\Devoirs\Entities\RenduDevoir;
use Modules\CoursHoraires\Entities\Cours;
use Modules\GestionApprenants\Entities\Apprenant;

class DevoirsDataSeeder extends Seeder
{
    public function run(): void
    {
        $cours = Cours::all();
        $apprenants = Apprenant::all();

        if ($cours->count() === 0) {
            $cours = Cours::factory(15)->create();
        }

        // Créer devoirs
        $devoirs = Devoir::factory(20)
            ->sequence(fn($sequence) => ['cours_id' => $cours->random()->id])
            ->create();

        // Créer rendus de devoirs
        $devoirs->each(function ($devoir) use ($apprenants) {
            $apprenants->random(rand(10, 25))->each(function ($apprenant) use ($devoir) {
                RenduDevoir::factory()
                    ->for($apprenant)
                    ->for($devoir)
                    ->create();
            });
        });

        $this->command->info('Devoirs data seeded successfully!');
    }
}
