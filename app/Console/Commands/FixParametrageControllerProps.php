<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixParametrageControllerProps extends Command
{
    protected $signature = 'fix:parametrage-controller-props';
    protected $description = 'Fix controller prop names to match Vue component expectations';

    public function handle()
    {
        $controllerDir = base_path('Modules/Parametrage/Http/Controllers');
        $files = glob("{$controllerDir}/*.php");

        $replacements = [
            "'anneesScolaires' => " => "'anneesScolaires' => ",  // Already correct (folder is plural)
            "'periodesColaires' => " => "'periodesColaires' => ",  // Already correct
            "'modesPaiement' => " => "'modesPaiement' => ",  // Was sending 'modesPaiements', should be 'modesPaiement'
            // Actually, let me check what format to use...
        ];

        $this->info("Checking controller prop names...");
        
        // Just scan and report for now
        foreach ($files as $file) {
            if (basename($file) === 'GlobalController.php') continue;
            
            $content = file_get_contents($file);
            if (preg_match("/'(\w+)'\s*=>\s*\\$\1/", $content, $matches)) {
                $this->line("  Found in " . basename($file) . ": " . $matches[1]);
            }
        }
    }
}
