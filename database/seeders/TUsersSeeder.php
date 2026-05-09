<?php

namespace Database\Seeders;

use App\Services\Generator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('users')->insert([
             [
             'nom' => 'TANOH',
             'prenoms' => 'VINCENT',
             'login' => '0747780473',
             'full_login' => '+2250747780473',
             'uuid' => Generator::uuid(),
             'alias_smil'=>'vincent_tanoh',
             'qr_data' => Generator::QrCode("0747780473"),
             'email' => 'mr.tanoh.vincent@gmail.com',
             'code_owner' => Generator::codeOwner(),
             'password' =>Hash::make("12345")
         ],
             [
                 'nom' => 'KOUASSI',
                 'prenoms' => 'JAURES',
                 'login' => '0787152058',
                 'full_login' => '+2250787152058',
                 'uuid' => Generator::uuid(),
                 'alias_smil'=>'jaures_kouassi',
                 'qr_data' => Generator::QrCode("0787152058"),
                 'email' => 'kouassijauressigl@gmail.com',
                 'code_owner' => Generator::codeOwner(),
                 'password' => Hash::make("12345")
             ]
         ]);
    }
}
