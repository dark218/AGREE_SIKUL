<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InitialisationProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Commande d'initialisation du projet";

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        -mv .env.example .env
//        -composer update
//    -npm install
//    -php artisan key:generate
//    -php artisan migrate:fresh --seed
//        exec("cp .env.example .env");
        exec("composer update");
//        exec("npm install");
        Artisan::call('key:generate');
        Artisan::call('migrate:fresh --seed');
    }
}
