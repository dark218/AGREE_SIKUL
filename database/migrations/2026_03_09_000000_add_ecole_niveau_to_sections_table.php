<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            if (!Schema::hasColumn('sections', 'ecole_id')) {
                $table->foreignId('ecole_id')->nullable()->after('annee_scolaire_id')->constrained('ecoles')->onDelete('set null');
            }
            if (!Schema::hasColumn('sections', 'niveau_etude_id')) {
                $table->foreignId('niveau_etude_id')->nullable()->after('ecole_id')->constrained('niveaux_etudes')->onDelete('set null');
            }
            if (Schema::hasColumn('sections', 'pays_id')) {
                $table->dropForeign(['pays_id']);
                $table->dropColumn('pays_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections', 'ecole_id')) {
                $table->dropForeign(['ecole_id']);
                $table->dropColumn('ecole_id');
            }
            if (Schema::hasColumn('sections', 'niveau_etude_id')) {
                $table->dropForeign(['niveau_etude_id']);
                $table->dropColumn('niveau_etude_id');
            }
            if (!Schema::hasColumn('sections', 'pays_id')) {
                $table->foreignId('pays_id')->nullable()->after('libelle')->constrained('pays')->onDelete('cascade');
            }
        });
    }
};
