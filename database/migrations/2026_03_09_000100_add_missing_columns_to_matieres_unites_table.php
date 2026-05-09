<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matieres_unites', function (Blueprint $table) {
            // Add type_matiere if it doesn't exist
            if (!Schema::hasColumn('matieres_unites', 'type_matiere')) {
                $table->enum('type_matiere', ['theorique', 'pratique', 'tp', 'td', 'projet'])->nullable()->after('coefficient');
            }

            // Add volume_horaire if it doesn't exist
            if (!Schema::hasColumn('matieres_unites', 'volume_horaire')) {
                $table->integer('volume_horaire')->nullable()->after('type_matiere');
            }

            // Add est_obligatoire if it doesn't exist
            if (!Schema::hasColumn('matieres_unites', 'est_obligatoire')) {
                $table->boolean('est_obligatoire')->default(false)->after('volume_horaire');
            }
        });
    }

    public function down(): void
    {
        Schema::table('matieres_unites', function (Blueprint $table) {
            if (Schema::hasColumn('matieres_unites', 'type_matiere')) {
                $table->dropColumn('type_matiere');
            }
            if (Schema::hasColumn('matieres_unites', 'volume_horaire')) {
                $table->dropColumn('volume_horaire');
            }
            if (Schema::hasColumn('matieres_unites', 'est_obligatoire')) {
                $table->dropColumn('est_obligatoire');
            }
        });
    }
};
