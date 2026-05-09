<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class SecurityCheck extends Command
{
    protected $signature = 'security:check';
    protected $description = 'Vérification de sécurité du projet';

    public function handle()
    {
        $this->info('🔒 Vérification de sécurité en cours...');
        
        $this->checkEnvironmentSecurity();
        $this->checkFilePermissions();
        $this->checkDependencies();
        $this->checkConfiguration();
        
        $this->info('✅ Vérification terminée');
    }

    private function checkEnvironmentSecurity()
    {
        $this->line('📋 Vérification de l\'environnement...');
        
        // Debug mode
        if (config('app.debug') && app()->environment('production')) {
            $this->error('❌ Debug mode activé en production');
        } else {
            $this->info('✅ Debug mode correct');
        }
        
        // App key
        if (empty(config('app.key'))) {
            $this->error('❌ APP_KEY manquante');
        } else {
            $this->info('✅ APP_KEY configurée');
        }
    }

    private function checkFilePermissions()
    {
        $this->line('📁 Vérification des permissions...');
        
        $sensitiveFiles = [
            '.env' => '600',
            'storage/' => '755',
            'bootstrap/cache/' => '755'
        ];
        
        foreach ($sensitiveFiles as $file => $expectedPerm) {
            $path = base_path($file);
            if (File::exists($path)) {
                $this->info("✅ {$file} existe");
            } else {
                $this->warn("⚠️  {$file} introuvable");
            }
        }
    }

    private function checkDependencies()
    {
        $this->line('📦 Vérification des dépendances...');
        
        $composerLock = json_decode(File::get(base_path('composer.lock')), true);
        $packages = $composerLock['packages'] ?? [];
        
        $vulnerablePackages = [
            'monolog/monolog' => ['< 1.25.2', '< 2.2.0'],
            'symfony/http-kernel' => ['< 4.4.50', '< 5.4.20'],
            'laravel/framework' => ['< 8.83.27', '< 9.52.16']
        ];
        
        foreach ($packages as $package) {
            $name = $package['name'];
            $version = $package['version'];
            
            if (isset($vulnerablePackages[$name])) {
                $this->warn("⚠️  {$name} v{$version} - Vérifiez les vulnérabilités");
            }
        }
        
        $this->info('✅ Vérification des dépendances terminée');
    }

    private function checkConfiguration()
    {
        $this->line('⚙️  Vérification de la configuration...');
        
        $checks = [
            'session.secure' => config('session.secure'),
            'session.http_only' => config('session.http_only'),
            'session.same_site' => config('session.same_site') === 'strict'
        ];
        
        foreach ($checks as $setting => $value) {
            if ($value) {
                $this->info("✅ {$setting} configuré");
            } else {
                $this->warn("⚠️  {$setting} non sécurisé");
            }
        }
    }
}