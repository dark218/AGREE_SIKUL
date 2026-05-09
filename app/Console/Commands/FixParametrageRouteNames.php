<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixParametrageRouteNames extends Command
{
    protected $signature = 'fix:parametrage-route-names';
    protected $description = 'Fix hyphenated route names to underscored in Vue pages';

    public function handle()
    {
        $vueDir = base_path('Modules/Parametrage/Resources/js/Pages');
        $files = $this->getAllVueFiles($vueDir);

        $replacements = [
            "route('annees-scolaires." => "route('annees_scolaires.",
            "route('periodes-colaires." => "route('periodes_colaires.",
            "route('modes-paiement." => "route('modes_paiement.",
            "route('cycles-enseignement." => "route('cycles_enseignement.",
            "route('types-enseignement." => "route('types_enseignement.",
            "route('types-etablissement." => "route('types_etablissement.",
            "route('niveaux-etudes." => "route('niveaux_etudes.",
            "route('types-cours." => "route('types_cours.",
            "route('natures-examens." => "route('natures_examens.",
            "route('types-examens." => "route('types_examens.",
            "route('matieres-unites." => "route('matieres_unites.",
            "route('groupes-matieres." => "route('groupes_matieres.",
            "route('types-apprenants." => "route('types_apprenants.",
            "route('categories-apprenants." => "route('categories_apprenants.",
            "route('titres-civilites." => "route('titres_civilites.",
            "route('types-evenements." => "route('types_evenements.",
            "route('unites-organisationnelles." => "route('unites_organisationnelles.",
            "route('types-ressources." => "route('types_ressources.",
            "route('natures-contrats." => "route('natures_contrats.",
            "route('categories-enseignants." => "route('categories_enseignants.",
            "route('jours-feries." => "route('jours_feries.",
        ];

        $fixedCount = 0;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $newContent = $content;

            foreach ($replacements as $old => $new) {
                if (strpos($newContent, $old) !== false) {
                    $newContent = str_replace($old, $new, $newContent);
                    $fixedCount++;
                }
            }

            if ($newContent !== $content) {
                file_put_contents($file, $newContent);
                $this->line("✓ Fixed: " . basename(dirname($file)) . "/" . basename($file));
            }
        }

        $this->info("\n✓ Fixed {$fixedCount} route name occurrences");
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
