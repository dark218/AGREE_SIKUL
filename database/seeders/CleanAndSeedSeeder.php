<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\GroupeMatiere;

class CleanAndSeedSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Delete existing data
        GroupeMatiere::truncate();
        MatiereUnite::truncate();
        NiveauEtude::truncate();
        Section::truncate();
        CycleEnseignement::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Get or create a default Pays
        $pays = Pays::firstOrCreate(['code' => 'COTE_IVOIRE'], ['libelle' => 'Côte d\'Ivoire']);

        // Créer des cycles FIRST (les niveaux d'étude en dépendent)
        $cycles = [
            ['code' => 'CYC_1', 'libelle' => 'Cycle 1', 'etat' => 'actif', 'pays_id' => $pays->id],
            ['code' => 'CYC_2', 'libelle' => 'Cycle 2', 'etat' => 'actif', 'pays_id' => $pays->id],
        ];

        foreach ($cycles as $c) {
            CycleEnseignement::create($c);
        }

        // Créer des niveaux d'étude
        $createdCycles = CycleEnseignement::all();
        $cycle = $createdCycles->first();
        $niveaux = [
            ['code' => 'NIVEAU_1', 'libelle' => '6ème', 'cycle_id' => $cycle->id, 'pays_id' => $pays->id],
            ['code' => 'NIVEAU_2', 'libelle' => '5ème', 'cycle_id' => $cycle->id, 'pays_id' => $pays->id],
            ['code' => 'NIVEAU_3', 'libelle' => '4ème', 'cycle_id' => $cycle->id, 'pays_id' => $pays->id],
        ];

        foreach ($niveaux as $n) {
            NiveauEtude::create($n);
        }

        // Créer des sections
        $sections = [
            ['code' => 'SEC_A', 'libelle' => 'Section A', 'etat' => 'actif'],
            ['code' => 'SEC_B', 'libelle' => 'Section B', 'etat' => 'actif'],
        ];

        foreach ($sections as $s) {
            Section::create($s);
        }

        // Get created records
        $niveau = NiveauEtude::first();
        $section = Section::first();

        // Créer des matières
        if ($niveau && $section && $cycle) {
            $matieres = [
                ['code' => 'MATH', 'libelle' => 'Mathématiques', 'niveau_id' => $niveau->id, 'section_id' => $section->id, 'cycle_id' => $cycle->id],
                ['code' => 'FRAN', 'libelle' => 'Français', 'niveau_id' => $niveau->id, 'section_id' => $section->id, 'cycle_id' => $cycle->id],
                ['code' => 'ANGL', 'libelle' => 'Anglais', 'niveau_id' => $niveau->id, 'section_id' => $section->id, 'cycle_id' => $cycle->id],
            ];

            foreach ($matieres as $m) {
                MatiereUnite::create($m);
            }
        }

        echo "✅ Base de données nettoyée et données de test créées!\n";
        echo "Niveaux: " . NiveauEtude::count() . "\n";
        echo "Sections: " . Section::count() . "\n";
        echo "Cycles: " . CycleEnseignement::count() . "\n";
        echo "Matières: " . MatiereUnite::count() . "\n";
    }
}
