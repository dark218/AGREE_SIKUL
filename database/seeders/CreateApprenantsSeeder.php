<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academique\Entities\Apprenant;
use Modules\Parametrage\Entities\Classe;

class CreateApprenantsSeeder extends Seeder
{
    public function run()
    {
        // Get some classes to assign apprenants to
        $classes = Classe::limit(5)->get();

        if ($classes->isEmpty()) {
            $this->command->error('Aucune classe trouvée. Créez d\'abord des classes.');
            return;
        }

        $prenoms = ['Ali', 'Fatou', 'Mohamed', 'Aminata', 'Ousmane', 'Hawa', 'Ibrahim', 'Mariam', 'Abdoulaye', 'Aïssatou', 'Mamadou', 'Ndeye', 'Souleymane', 'Khady', 'Cheikh'];
        $noms = ['Diallo', 'Ba', 'Sow', 'Sy', 'Ndiaye', 'Sall', 'Gueye', 'Sarr', 'Toure', 'Cisse', 'Diouf', 'Mbaye', 'Faye', 'Niasse', 'Beye'];
        $sexes = ['M', 'F'];

        $apprenants = [];

        foreach (range(1, 15) as $i) {
            $prenomIndex = ($i - 1) % count($prenoms);
            $nomIndex = ($i - 1) % count($noms);
            $prenom = $prenoms[$prenomIndex];
            $nom = $noms[$nomIndex];
            $sexe = $sexes[($i - 1) % 2];
            $classe = $classes->random();

            $apprenants[] = [
                'matricule' => 'MAT' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'numero_inscription' => 'INS' . date('Y') . str_pad($i, 3, '0', STR_PAD_LEFT),
                'nom' => $nom,
                'prenoms' => $prenom,
                'email' => strtolower($prenom . '.' . $nom . $i . '@school.edu'),
                'telephone' => '+221' . rand(700000000, 799999999),
                'date_naissance' => date('Y-m-d', strtotime('-15 years +' . rand(0, 365) . ' days')),
                'sexe' => $sexe,
                'nationalite' => 'Sénégalaise',
                'groupe_sanguin' => ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'][rand(0, 7)],
                'adresse' => 'Adresse ' . $i,
                'classe_id' => $classe->id,
                'section_id' => $classe->section_id,
                'cycle_id' => $classe->cycle_id,
                'ecole_id' => $classe->ecole_id,
                'campus_id' => $classe->campus_id,
                'annee_scolaire_id' => $classe->annee_scolaire_id,
                'statut' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Apprenant::insert($apprenants);
        $this->command->info('✅ 15 apprenants créés avec succès!');
    }
}
