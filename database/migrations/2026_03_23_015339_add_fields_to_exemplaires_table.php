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
        Schema::table('exemplaires', function (Blueprint $table) {
            // Colonnes ajoutées manuellement - vérification pour idempotence
            if (!Schema::hasColumn('exemplaires', 'numero_serie')) {
                $table->string('numero_serie')->nullable()->after('code_exemplaire');
            }
            if (!Schema::hasColumn('exemplaires', 'localisation')) {
                $table->string('localisation')->nullable()->after('etat');
            }
            if (!Schema::hasColumn('exemplaires', 'date_acquisition')) {
                $table->date('date_acquisition')->nullable()->after('localisation');
            }
            if (!Schema::hasColumn('exemplaires', 'date_derniere_maintenance')) {
                $table->date('date_derniere_maintenance')->nullable()->after('date_acquisition');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exemplaires', function (Blueprint $table) {
            $table->dropColumn(['numero_serie', 'localisation', 'date_acquisition', 'date_derniere_maintenance']);
        });
    }
};
