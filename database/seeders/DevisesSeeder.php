<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevisesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate if exists
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('devises')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::table('devises')->insert([
            // West African Franc (WAEMU - UEMOA)
            [
                'id' => 1,
                'code' => "XOF-WAEMU",
                'code_iso' => "XOF",
                'symbol' => "CFA",
                'libelle' => "Franc CFA UEMOA",
                'decimal_places' => 0,
                'exchange_rate' => 1.000000,
                'is_default' => true,
                'pays_id' => null,
                'etat' => 'actif',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Central African Franc (CEMAC)
            [
                'id' => 2,
                'code' => "XAF-CEMAC",
                'code_iso' => "XAF",
                'symbol' => "FCFA",
                'libelle' => "Franc CFA CEMAC",
                'decimal_places' => 0,
                'exchange_rate' => 1.000000,
                'is_default' => false,
                'pays_id' => null,
                'etat' => 'actif',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // US Dollar
            [
                'id' => 3,
                'code' => "USD-USA",
                'code_iso' => "USD",
                'symbol' => "$",
                'libelle' => "Dollar américain",
                'decimal_places' => 2,
                'exchange_rate' => 610.500000,
                'is_default' => false,
                'pays_id' => null,
                'etat' => 'actif',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Euro
            [
                'id' => 4,
                'code' => "EUR-EU",
                'code_iso' => "EUR",
                'symbol' => "€",
                'libelle' => "Euro",
                'decimal_places' => 2,
                'exchange_rate' => 656.500000,
                'is_default' => false,
                'pays_id' => null,
                'etat' => 'actif',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Guinean Franc
            [
                'id' => 5,
                'code' => "GNF-GUINEA",
                'code_iso' => "GNF",
                'symbol' => "FG",
                'libelle' => "Franc guinéen",
                'decimal_places' => 0,
                'exchange_rate' => 1.000000,
                'is_default' => false,
                'pays_id' => null,
                'etat' => 'actif',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Congolese Franc
            [
                'id' => 6,
                'code' => "CDF-DRC",
                'code_iso' => "CDF",
                'symbol' => "FC",
                'libelle' => "Franc congolais",
                'decimal_places' => 0,
                'exchange_rate' => 1.000000,
                'is_default' => false,
                'pays_id' => null,
                'etat' => 'actif',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Canadian Dollar
            [
                'id' => 7,
                'code' => "CAD-CANADA",
                'code_iso' => "CAD",
                'symbol' => "$",
                'libelle' => "Dollar canadien",
                'decimal_places' => 2,
                'exchange_rate' => 450.750000,
                'is_default' => false,
                'pays_id' => null,
                'etat' => 'actif',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

}
