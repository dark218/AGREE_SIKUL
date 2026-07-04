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
        // idempotence guard
        Schema::table('absences_enseignants', function (Blueprint $table) {
            if (!Schema::hasColumn('absences_enseignants', 'etat')) {
                $table->enum('etat', ['actif', 'inactif'])->default('actif')->after('statut');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absences_enseignants', function (Blueprint $table) {
            if (Schema::hasColumn('absences_enseignants', 'etat')) {
                $table->dropColumn('etat');
            }
        });
    }
};
