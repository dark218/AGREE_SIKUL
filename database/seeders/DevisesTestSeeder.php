<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevisesTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('devises')->insert([
            [
                'id' => 1,
                'code' => "XOF",
                'symbole' => "XOF",
                'libelle' => "Franc CFA",
                'decimal_point' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                'id' => 2,
                'code' => "CAD",
                'symbole' => "$ CA",
                'libelle' => "Dollards canadiens",
                'decimal_point' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                'id' => 3,
                'code' => "EUR",
                'symbole' => "€",
                'libelle' => "EURO",
                'decimal_point' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'code' => "XAF",
                'symbole' => "XAF",
                'libelle' => "Franc CFA CEMAC",
                'decimal_point' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'code' => "GNF",
                'symbole' => "GNF",
                'libelle' => "Franc guinéen",
                'decimal_point' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'code' => "CDF",
                'symbole' => "CDF",
                'libelle' => "Franc congolais",
                'decimal_point' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'code' => "USD",
                'symbole' => "$",
                'libelle' => "Dollards américains",
                'decimal_point' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

}
