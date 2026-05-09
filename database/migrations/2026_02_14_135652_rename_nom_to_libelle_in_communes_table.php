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
        if (Schema::hasTable('communes')) if (Schema::hasTable('communes')) Schema::table('communes', function (Blueprint $table) {
            $table->renameColumn('nom', 'libelle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('communes')) if (Schema::hasTable('communes')) Schema::table('communes', function (Blueprint $table) {
            $table->renameColumn('libelle', 'nom');
        });
    }
};
