<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Academique\Entities\Enseignant;
use Modules\Academique\Entities\AbsenceEnseignant;
use Modules\Academique\Entities\Matiere;
use Modules\Parametrage\Entities\Classe;
use Carbon\Carbon;

class AbsenceEnseignantTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get enseignants
        $enseignants = Enseignant::limit(6)->get();

        if ($enseignants->isEmpty()) {
            echo "❌ No enseignants found. Create them first.\n";
            return;
        }

        // Get matieres
        $matieres = Matiere::whereIn('libelle', ['Français', 'Mathématiques', 'Anglais', 'Sciences Naturelles', 'Histoire-Géographie'])
            ->get();

        if ($matieres->isEmpty()) {
            echo "❌ Matieres not found. Create them first using MatiereClasseTestSeeder\n";
            return;
        }

        // Get classes
        $classes = Classe::limit(5)->get();

        if ($classes->isEmpty()) {
            echo "❌ No classes found in database.\n";
            return;
        }

        $motifs = [
            'Maladie',
            'Rendez-vous médical',
            'Congé personnel',
            'Événement familial',
            'Raison administrative',
            'Autre raison justifiée',
        ];

        $statuts = ['en_attente', 'validee', 'rejetee'];
        $counter = 0;

        // Create 12 test absences (2 per enseignant)
        foreach ($enseignants as $enseignant) {
            for ($i = 0; $i < 2; $i++) {
                $dateDebut = Carbon::now()->addDays(rand(1, 30))->setHour(rand(8, 16))->setMinute(0);
                $dateFin = (clone $dateDebut)->addHours(rand(1, 4));

                // Calculate duration in hours
                $dureeHeures = $dateFin->diffInMinutes($dateDebut) / 60;

                AbsenceEnseignant::create([
                    'enseignant_id' => $enseignant->id,
                    'matiere_id' => $matieres->random()->id,
                    'classe_id' => $classes->random()->id,
                    'date_debut' => $dateDebut,
                    'date_fin' => $dateFin,
                    'nombre_heures' => $dureeHeures,
                    'motif' => $motifs[array_rand($motifs)],
                    'statut' => $statuts[array_rand($statuts)],
                    'etat' => 'actif',
                ]);

                $counter++;
            }
        }

        echo "✅ $counter absence enseignant entries created!\n";
    }
}
