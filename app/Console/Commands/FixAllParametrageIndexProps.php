<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FixAllParametrageIndexProps extends Command
{
    protected $signature = 'fix:parametrage-index-props';
    protected $description = 'Fix all Parametrage Index.vue prop definitions to match their controllers';

    public function handle()
    {
        $this->info("🔧 Fixing all Parametrage Index.vue prop definitions...\n");

        $basePath = base_path('Modules/Parametrage/Resources/js/Pages');
        $fixed = 0;
        $skipped = 0;

        // Mapping of Index.vue folder to correct prop name
        $mappings = [
            'AnneesScolaires' => 'anneesScolaires',
            'Banques' => 'banques',
            'Campuses' => 'campuses',
            'CategoriesApprenant' => 'categories',
            'CategoriesEnseignant' => 'categories',
            'Classes' => 'classes',
            'Communes' => 'communes',
            'CyclesEnseignement' => 'cycles',
            'Departements' => 'departements',
            'Devises' => 'devises',
            'DevisesPays' => 'devisesPays',
            'Ecoles' => 'ecoles',
            'Fichiers' => 'fichiers',
            'Fonctions' => 'fonctions',
            'FournisseursPaiement' => 'fournisseurs',
            'GroupesMatiere' => 'groupes',
            'Institutions' => 'institutions',
            'JoursFeries' => 'jours',
            'MatiereUnites' => 'matieres',
            'MatieresUnites' => 'matieres',
            'ModesPaiement' => 'modes',
            'NaturesContrat' => 'natures',
            'NaturesExamen' => 'natures',
            'NatureExamens' => 'natures',
            'Niveaux' => 'niveaux',
            'NiveauxEtudes' => 'niveaux',
            'NiveauxÉtude' => 'niveaux',
            'Pays' => 'pays',
            'PeriodesColaires' => 'periodes',
            'Quartiers' => 'quartiers',
            'Regions' => 'regions',
            'Sections' => 'sections',
            'TitreCivilites' => 'titres',
            'TitresCivilite' => 'titres',
            'TypeApprenants' => 'types',
            'TypesCours' => 'types',
            'TypeEtablissements' => 'types',
            'TypeExamens' => 'types',
            'TypesApprenant' => 'types',
            'TypesEnseignement' => 'types',
            'TypesExamen' => 'types',
            'TypesRessource' => 'types',
            'TypesÉvenement' => 'types',
            'TypesÉtablissement' => 'types',
            'UniteOrganisationnelles' => 'unites',
            'UnitesOrganisationnelles' => 'unites',
            'Zones' => 'zones',
        ];

        foreach ($mappings as $folder => $correctProp) {
            $indexPath = "{$basePath}/{$folder}/Index.vue";

            if (!file_exists($indexPath)) {
                $this->line("⏭️  Skipped: $folder (file not found)");
                $skipped++;
                continue;
            }

            $content = file_get_contents($indexPath);

            // Check if file has the wrong prop
            if (strpos($content, "'natureContrats'") !== false || strpos($content, '"natureContrats"') !== false) {
                // Fix the prop definition
                $oldPropsPattern = "const props = defineProps\(\{[\s\S]*?title: String,[\s\S]*?nature(?:Contrats|contrats): Object,";
                $newPropsDefinition = "const props = defineProps({
    title: String,
    {$correctProp}: Object,";

                // Use regex to replace
                $newContent = preg_replace(
                    "/const props = defineProps\(\{[\s\n]*title: String,[\s\n]*(nature[A-Za-z]*: Object,)/",
                    "const props = defineProps({\n    title: String,\n    {$correctProp}: Object,",
                    $content
                );

                // Add the variable definition after 'const props'
                if (strpos($newContent, "const {$correctProp} = props.{$correctProp}") === false) {
                    $addVarDefinition = "const {$correctProp} = props.{$correctProp} || page.props.{$correctProp};\n";
                    $newContent = preg_replace(
                        '/(const props = defineProps\(\{[\s\S]*?\}\);)/',
                        "$1\n\n// Make prop accessible in template\n$addVarDefinition",
                        $newContent
                    );
                }

                file_put_contents($indexPath, $newContent);
                $this->line("✅ Fixed: {$folder} ({$correctProp})");
                $fixed++;
            } else {
                $this->line("✅ Already OK: {$folder}");
            }
        }

        $this->info("\n" . str_repeat("=", 50));
        $this->line("Summary:");
        $this->line("- Total files processed: " . count($mappings));
        $this->line("- Fixed: $fixed");
        $this->line("- Skipped: $skipped");
        $this->line(str_repeat("=", 50) . "\n");

        // Clear cache
        \Artisan::call('cache:clear');
        $this->line("✓ Cache cleared\n");

        $this->info("✨ All Parametrage Index.vue files have been fixed!");
        $this->line("📝 The props now correctly match what their controllers pass");
    }
}
