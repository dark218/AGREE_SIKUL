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
        if (Schema::hasTable('postes_recettes') && !Schema::hasColumn('postes_recettes', 'compte_comptable')) {
            Schema::table('postes_recettes', function (Blueprint $table) {
                $table->string('compte_comptable')->nullable()->after('libelle');
            });
        }

        if (Schema::hasTable('postes_depenses') && !Schema::hasColumn('postes_depenses', 'compte_comptable')) {
            Schema::table('postes_depenses', function (Blueprint $table) {
                $table->string('compte_comptable')->nullable()->after('libelle');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postes_recettes', function (Blueprint $table) {
            $table->dropColumn('compte_comptable');
        });

        Schema::table('postes_depenses', function (Blueprint $table) {
            $table->dropColumn('compte_comptable');
        });
    }
};
