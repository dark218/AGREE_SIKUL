<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate the module table to avoid duplicate key errors
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('module')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('module')->insert([
            // MODULES AGREE SIKUL - GESTION SCOLAIRE (16)
            
            
            
            // [
            //     'id' => 4,
            //     'libelle' => 'Gestion Apprenants',
            //     'libelle_en' => 'Student Management',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-users",
            //     'ordre' => 97,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => 5,
            //     'libelle' => 'Enseignants & RH',
            //     'libelle_en' => 'Teachers & HR',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-chalkboard-user",
            //     'ordre' => 96,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => 6,
            //     'libelle' => 'Cours & Horaires',
            //     'libelle_en' => 'Courses & Schedules',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-calendar-alt",
            //     'ordre' => 95,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => 7,
            //     'libelle' => 'Présence & Absences',
            //     'libelle_en' => 'Attendance',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-check-circle",
            //     'ordre' => 94,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => 8,
            //     'libelle' => 'Examens & Notes',
            //     'libelle_en' => 'Exams & Grades',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-file-alt",
            //     'ordre' => 93,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => 9,
            //     'libelle' => 'Devoirs',
            //     'libelle_en' => 'Homework',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-pencil-alt",
            //     'ordre' => 92,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            [
                'id' => 10,
                'libelle' => 'Communication',
                'libelle_en' => 'Communication',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-comments",
                'ordre' => 91,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'libelle' => 'Finances',
                'libelle_en' => 'Finance',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-money-bill",
                'ordre' => 90,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // [
            //     'id' => 12,
            //     'libelle' => 'Bibliothèque',
            //     'libelle_en' => 'Library',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-book-open",
            //     'ordre' => 89,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            [
                'id' => 13,
                'libelle' => 'Services',
                'libelle_en' => 'Services',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-concierge-bell",
                'ordre' => 88,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 14,
                'libelle' => 'Documents',
                'libelle_en' => 'Documents',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-file",
                'ordre' => 87,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // [
            //     'id' => 15,
            //     'libelle' => 'Inventaire',
            //     'libelle_en' => 'Inventory',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-boxes",
            //     'ordre' => 86,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            [
                'id' => 16,
                'libelle' => 'Rapports',
                'libelle_en' => 'Reports',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-chart-bar",
                'ordre' => 85,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // MODULES SMILPAY EXISTANTS (6)
            // [
            //     'id' => 17,
            //     'libelle' => 'Business',
            //     'libelle_en' => 'Business',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-handshake",
            //     'ordre' => 50,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            [
                'id' => 18,
                'libelle' => 'Personnel',
                'libelle_en' => 'Personnel',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-users-cog",
                'ordre' => 49,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // [
            //     'id' => 19,
            //     'libelle' => 'Service Client',
            //     'libelle_en' => 'Customer Service',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-headset",
            //     'ordre' => 48,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => 20,
            //     'libelle' => 'POS',
            //     'libelle_en' => 'Point of Sale',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-cash-register",
            //     'ordre' => 47,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => 21,
            //     'libelle' => 'Gestion Stock',
            //     'libelle_en' => 'Stock Management',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-warehouse",
            //     'ordre' => 46,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => 22,
            //     'libelle' => 'Wallet',
            //     'libelle_en' => 'Digital Wallet',
            //     'menu_url' => 'javascript:;',
            //     'icone' => "fas fa-wallet",
            //     'ordre' => 45,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // GESTION ACADEMIQUE CONSOLIDEE
            [
                'id' => 25,
                'libelle' => 'Académique',
                'libelle_en' => 'Academic',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-graduation-cap",
                'ordre' => 97,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // CONFIGURATION & ADMINISTRATION
            [
                'id' => 23,
                'libelle' => 'Paramétrage',
                'libelle_en' => 'Settings',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-cog",
                'ordre' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 24,
                'libelle' => 'Administration',
                'libelle_en' => 'Administration',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-tools",
                'ordre' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 26,
                'libelle' => 'Ressources Logistique',
                'libelle_en' => 'Logistic Resources',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-dolly",
                'ordre' => 84,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Ajouté : module 27 (Paramétrage Généraux) était référencé par la
            // feature id 166 sans exister ici → FK cassée au seed.
            [
                'id' => 27,
                'libelle' => 'Paramétrage Généraux',
                'libelle_en' => 'General Settings',
                'menu_url' => 'javascript:;',
                'icone' => "fas fa-sliders-h",
                'ordre' => 19,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
