<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmploiTempsTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the classe that apprenants are assigned to
        $classe = \Modules\Parametrage\Entities\Classe::find(2) ?? \Modules\Parametrage\Entities\Classe::first();

        if (!$classe) {
            echo "❌ No classes found in database.\n";
            return;
        }

        $matieres = \Modules\Parametrage\Entities\MatiereUnite::whereIn('libelle', ['Français', 'Mathématiques', 'Anglais', 'Sciences Naturelles', 'Histoire-Géographie'])
            ->get();

        if ($matieres->isEmpty()) {
            echo "❌ Matieres not found. Create them first using MatiereClasseTestSeeder\n";
            return;
        }

        // Get an active academic year
        $anneeScolaire = \Modules\Parametrage\Entities\AnneeScolaire::where('etat', 'actif')->first();

        if (!$anneeScolaire) {
            echo "❌ No active academic year found in database.\n";
            return;
        }

        // Create schedule slots - multiple courses per day
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
        $heures = [
            ['debut' => '08:00', 'fin' => '09:30', 'duree' => 1.5],
            ['debut' => '09:45', 'fin' => '11:15', 'duree' => 1.5],
            ['debut' => '11:30', 'fin' => '13:00', 'duree' => 1.5],
            ['debut' => '13:30', 'fin' => '15:00', 'duree' => 1.5],
            ['debut' => '15:15', 'fin' => '16:45', 'duree' => 1.5],
        ];

        $counter = 0;

        // Create multiple courses for each day
        foreach ($jours as $jour) {
            foreach ($heures as $heure) {
                // Get a matiere (rotate through available matieres)
                $matiere = $matieres[$counter % $matieres->count()];

                // Use Carbon to create proper datetime objects
                $today = now();
                $dateDebut = \Carbon\Carbon::parse($today->format('Y-m-d') . ' ' . $heure['debut']);
                $dateFin = \Carbon\Carbon::parse($today->format('Y-m-d') . ' ' . $heure['fin']);

                // Calculate duration from start and end times (in hours)
                $dureeCalculee = $dateFin->diffInMinutes($dateDebut) / 60;

                \Modules\Academique\Entities\EmploiTemps::create([
                    'annee_scolaire_id' => $anneeScolaire->id,
                    'classe_id' => $classe->id,
                    'matiere_id' => $matiere->id,
                    'jour' => $jour,
                    'duree' => $dureeCalculee, // Calculate from dates instead of hardcoding
                    'date_debut' => $dateDebut,
                    'date_fin' => $dateFin,
                    'est_valide' => true,
                    'statut' => 'publie',
                ]);
                $counter++;
            }
        }

        echo "✅ $counter emploi du temps entries created for classe: {$classe->nom}\n";
    }
}
