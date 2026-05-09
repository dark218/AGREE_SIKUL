<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('departements')) if (Schema::hasTable('departements')) Schema::table('departements', function (Blueprint $table) {
            $table->renameColumn('libelle', 'nom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('departements')) if (Schema::hasTable('departements')) Schema::table('departements', function (Blueprint $table) {
            $table->renameColumn('nom', 'libelle');
        });
    }
};
