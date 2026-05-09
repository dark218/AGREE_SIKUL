<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TModuleTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('module')->insert([
            [
                'id' => 1,
                'libelle' => 'Administration',
                'libelle_en' => 'Administration',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-user-cog",
                'ordre' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'libelle' => 'Paramétrage',
                'libelle_en' => 'Setting',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-cog",
                'ordre' => 19,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'libelle' => 'Business',
                'libelle_en' => 'Business',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-store",
                'ordre' => 17,
                'created_at' => now(),
                'updated_at' => now(),
            ],
              [
                 'id' => 4,
                 'libelle' => 'Personnel',
                 'libelle_en' => 'Personnel',
                 'menu_url' => 'javascript:;',
                 'icone' => "fas fa-user-tie",
                 'ordre' => 18,
                 'created_at' => now(),
                 'updated_at' => now(),
             ],
            [
                'id' => 5,
                'libelle' => 'Service Client',
                'libelle_en' => 'Service Client',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-handshake",
                'ordre' => 16,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        [
                'id' => 6,
                'libelle' => 'POS',
                'libelle_en' => 'POS',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-map-pin",
                'ordre' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        [
                'id' => 7,
                'libelle' => 'Gestion stock',
                'libelle_en' => 'Stock management',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-archive",
                'ordre' => 14,
                'created_at' => now(),
                'updated_at' => now(),
            ],
             [
                'id' => 8,
                'libelle' => 'Portefeuille',
                'libelle_en' => 'Wallet',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-wallet",
                'ordre' => 13,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
