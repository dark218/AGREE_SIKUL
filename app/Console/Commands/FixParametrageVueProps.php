<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixParametrageVueProps extends Command
{
    protected $signature = 'fix:parametrage-vue-props';
    protected $description = 'Fix Vue prop names in generated pages to match controllers';

    public function handle()
    {
        $vueDir = base_path('Modules/Parametrage/Resources/js/Pages');
        $files = $this->getAllVueFiles($vueDir);

        // Map folder names to expected prop names (what controllers will send)
        $propMappings = [
            'ModesPaiement' => 'modesPaiement',
            'AnneesScolaires' => 'anneesScolaires',
            'PeriodesColaires' => 'periodesColaires',
            'CategoriesApprenant' => 'categorieApprenant',
            'CategoriesEnseignant' => 'categorieEnseignant',
            'CyclesEnseignement' => 'cycleEnseignement',
            'GroupesMatiere' => 'groupeMatiere',
            'JoursFeries' => 'jourFerie',
            'MatieresUnites' => 'matiereUnite',
            'NaturesContrat' => 'natureContrat',
            'NaturesExamen' => 'natureExamen',
            'NiveauxÉtude' => 'niveauEtude',
            'PeriodesColaires' => 'periodeColaire',
            'TitresCivilite' => 'titreCivilite',
            'TypesApprenant' => 'typeApprenant',
            'TypesCours' => 'typeCours',
            'TypesEnseignement' => 'typeEnseignement',
            'TypesExamen' => 'typeExamen',
            'TypesRessource' => 'typeRessource',
            'TypesÉtablissement' => 'typeEtablissement',
            'TypesÉvenement' => 'typeEvenement',
            'UnitesOrganisationnelles' => 'uniteOrganisationnelle',
        ];

        $fixedCount = 0;

        foreach ($files as $file) {
            $folderName = basename(dirname($file));
            
            if (!isset($propMappings[$folderName])) {
                continue;
            }

            $oldProp = lcfirst($folderName); // e.g., modesPaiement
            $newProp = $propMappings[$folderName]; // e.g., modesPaiement

            if ($oldProp === $newProp) {
                continue;
            }

            $content = file_get_contents($file);
            $newContent = str_replace($oldProp, $newProp, $content);

            if ($newContent !== $content) {
                file_put_contents($file, $newContent);
                $this->line("✓ Fixed: {$folderName}/$(basename($file))");
                $fixedCount++;
            }
        }

        $this->info("\n✓ Fixed {$fixedCount} Vue prop references");
    }

    private function getAllVueFiles($dir)
    {
        $files = [];
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                if ($file !== '.' && $file !== '..') {
                    $path = $dir . '/' . $file;
                    if (is_dir($path)) {
                        $files = array_merge($files, $this->getAllVueFiles($path));
                    } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'vue') {
                        $files[] = $path;
                    }
                }
            }
        }
        return $files;
    }
}
