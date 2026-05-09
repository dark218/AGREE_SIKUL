<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetDevMode extends Command
{
    protected $signature = 'dev:mode {--disable : Disable dev mode}';
    protected $description = 'Enable/Disable development mode (bypass permissions)';

    public function handle()
    {
        $env_file = base_path('.env');
        $env_content = file_get_contents($env_file);

        $disable = $this->option('disable');

        if ($disable) {
            // Remove DEV_MODE
            $env_content = preg_replace('/^DEV_MODE=.*$/m', '', $env_content);
            $this->line("✓ Development mode DISABLED");
            file_put_contents($env_file, $env_content);
            \Artisan::call('config:cache');
            return 0;
        }

        // Check if already set
        if (strpos($env_content, 'DEV_MODE=true') !== false) {
            $this->line("✓ Development mode already ENABLED");
            return 0;
        }

        // Add DEV_MODE=true
        $env_content .= "\n# Development mode - bypass permissions\nDEV_MODE=true\n";
        file_put_contents($env_file, $env_content);

        \Artisan::call('config:cache');
        $this->info("✓ Development mode ENABLED!");
        $this->line("⚠️  Permissions checks will be BYPASSED");

        return 0;
    }
}
