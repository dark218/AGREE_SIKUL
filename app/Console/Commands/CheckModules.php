<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckModules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-modules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modules = \DB::table('module')->orderBy('id')->get(['id', 'libelle', 'ordre', 'deleted_at']);
        $features = \DB::table('feature')->orderBy('id')->get(['id', 'libelle', 'module_id', 'deleted_at']);

        $this->info('=== MODULES (with ordre) ===');
        foreach ($modules as $m) {
            $status = $m->deleted_at ? '❌ DELETED' : '✓ ACTIVE';
            $this->line("{$m->id} | {$m->libelle} | ordre={$m->ordre} | {$status}");
        }

        $this->info("\n=== FEATURES BY MODULE ===");
        foreach ($modules as $m) {
            $moduleFeatures = collect($features)->filter(fn($f) => $f->module_id == $m->id);
            $this->line("{$m->id}. {$m->libelle}: {$moduleFeatures->count()} features");
            foreach ($moduleFeatures as $f) {
                $status = $f->deleted_at ? '❌ DELETED' : '✓ ACTIVE';
                $this->line("   - {$f->libelle} {$status}");
            }
        }

        $this->info("\nTotal modules: " . count($modules));
        $this->info("Total features: " . count($features));
    }
}
