<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pays')->insert([
            [
                'id' => 1,
                'libelle' => "Côte d'ivoire",
                'code' => '+225',
                'code_2_chars' => 'CI',
                'code_3_chars' => 'CIV',
                'continent' => 'Afrique',
                'nombre' => '0',
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'libelle' => "Mali",
                'code' => '+223',
                'code_2_chars' => 'ML',
                'code_3_chars' => 'MLI',
                'continent' => 'Afrique',
                'nombre' => '0',
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'libelle' => "Burkina Faso",
                'code' => '+226',
                'code_2_chars' => 'BF',
                'code_3_chars' => 'BFA',
                'continent' => 'Afrique',
                'nombre' => '0',
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'libelle' => "Sénegal",
                'code' => '+221',
                'code_2_chars' => 'SN',
                'code_3_chars' => 'SEN',
                'continent' => 'Afrique',
                'nombre' => '0',
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'libelle' => "Bénin",
                'code' => '+229',
                'code_2_chars' => 'BJ',
                'code_3_chars' => 'BEN',
                'continent' => 'Afrique',
                'nombre' => '0',
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'libelle' => "Cameroon",
                'code' => '+237',
                'code_2_chars' => 'CM',
                'code_3_chars' => 'CMR',
                'continent' => 'Afrique',
                'nombre' => '0',
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'libelle' => "Guinée",
                'code' => '+224',
                'code_2_chars' => 'GN',
                'code_3_chars' => 'GIN',
                'continent' => 'Afrique',
                'nombre' => '0',
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'libelle' => "République démocratique du Congo",
                'code' => '+243',
                'code_2_chars' => 'CD',
                'code_3_chars' => 'COD',
                'continent' => 'Afrique',
                'nombre' => '0',
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'libelle' => "Niger",
                'code' => '+227',
                'code_2_chars' => 'NE',
                'code_3_chars' => 'NER',
                'continent' => 'Afrique',
                'nombre' => '0',
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'libelle' => "Togo",
                'code' => '+228',
                'code_2_chars' => 'TG',
                'code_3_chars' => 'TGO',
                'continent' => 'Afrique',
                'nombre' => '0',
                'etat' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
