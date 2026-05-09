<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DebugNavbars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:debug-navbars';

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
        $user = \DB::table('users')->where('login', '0700000001')->first();
        if (!$user) {
            $this->error('Super Admin user not found');
            return;
        }

        // Create a mock user object with roles
        $user = \App\Models\User::find($user->id);
        $isSuperAdmin = $user->hasRole('super_admin');

        $this->info("User: {$user->login}");
        $this->info("Is Super Admin: " . ($isSuperAdmin ? 'YES' : 'NO'));
        $this->info("Roles: " . $user->roles->pluck('name')->join(', '));

        $modules = \Modules\Administration\Entities\Module::actif()
            ->with(['fonctionnalitesActives' => function ($query) {
                $query->orderBy('ordre');
            }])
            ->orderBy('ordre')
            ->get();

        $this->info("\n=== UNFILTERED MODULES ===");
        foreach ($modules as $m) {
            $this->line("  {$m->id}. {$m->libelle} ({$m->fonctionnalitesActives->count()} features)");
        }

        $filtered = $modules->filter(fn($module) => $isSuperAdmin || $module->peutVoir($user));

        $this->info("\n=== FILTERED MODULES (after peutVoir filter) ===");
        foreach ($filtered as $m) {
            $features = $m->fonctionnalitesActives
                ->filter(fn($f) => $isSuperAdmin || $f->peutVoir($user));
            $this->line("  {$m->id}. {$m->libelle} ({$features->count()} visible features)");
            foreach ($features as $f) {
                $this->line("     - {$f->libelle} ({$f->menu_url})");
            }
        }

        $this->info("\nTotal filtered: " . $filtered->count() . " / " . $modules->count());
    }
}
