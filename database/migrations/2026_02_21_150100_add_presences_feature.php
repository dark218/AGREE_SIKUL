<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // idempotence guard
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('feature')->insert([
            [
                'id' => 123,
                'libelle' => 'Présences',
                'libelle_en' => 'Attendance',
                'module_id' => 25,
                'menu_url' => 'presences',
                'icone' => 'fas fa-user-check',
                'ordre' => 21,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('feature')->where('id', 123)->delete();
    }
};
