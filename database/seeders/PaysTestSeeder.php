<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaysTestSeeder extends Seeder
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
                'phone_length' => '10',
                'iso' => 'CI',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'libelle' => "Mali",
                'code' => '+223',
                'phone_length' => '8',
                'iso' => 'ML',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'libelle' => "Burkina Faso",
                'code' => '+226',
                'phone_length' => '8',
                'iso' => 'BF',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'libelle' => "Sénegal",
                'code' => '+221',
                'phone_length' => '9',
                'iso' => 'SN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'libelle' => "Bénin",
                'code' => '+229',
                'phone_length' => '10',
                'iso' => 'BJ',
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                'id' => 6,
                'libelle' => "Cameroon",
                'code' => '+237',
                'phone_length' => '9',
                'iso' => 'CM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'libelle' => "Guinée",
                'code' => '+224',
                'phone_length' => '8',
                'iso' => 'GN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'libelle' => "République démocratique du Congo",
                'code' => '+243',
                'phone_length' => '9',
                'iso' => 'CD',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'libelle' => "Niger",
                'code' => '+227',
                'phone_length' => '8',
                'iso' => 'NE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'libelle' => "Togo",
                'code' => '+228',
                'phone_length' => '8',
                'iso' => 'TG',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
