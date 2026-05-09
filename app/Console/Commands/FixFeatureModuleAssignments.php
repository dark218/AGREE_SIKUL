<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixFeatureModuleAssignments extends Command
{
    protected $signature = 'fix:feature-module-assignments';
    protected $description = 'Fix feature module_id assignments (modules 4-9 were incorrectly assigned to module 25)';

    public function handle()
    {
        $this->info("Fixing feature module assignments...\n");

        $assignments = [
            // Module 4: Gestion Apprenants
            4 => [4, 32, 33, 34],
            // Module 5: Enseignants & RH
            5 => [5, 35, 36],
            // Module 6: Cours & Horaires
            6 => [6, 37, 38, 39],
            // Module 7: Présence & Absences
            7 => [7, 40, 41],
            // Module 8: Examens & Notes
            8 => [8, 42, 43, 44],
            // Module 9: Devoirs
            9 => [9, 45],
        ];

        foreach ($assignments as $moduleId => $featureIds) {
            $count = DB::table('feature')
                ->whereIn('id', $featureIds)
                ->update(['module_id' => $moduleId]);

            $this->line("✓ Updated {$count} features to module_id {$moduleId}");
        }

        // Clear cache
        \Artisan::call('cache:clear');
        $this->line("✓ Cache cleared");

        $this->info("\n✨ Feature module assignments fixed!");
        $this->line("📋 Features are now correctly organized in their respective modules");
    }
}
