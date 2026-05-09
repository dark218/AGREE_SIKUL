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
        if (!Schema::hasTable('classes')) {
            return; // Table doesn't exist, skip
        }

        // Check if columns already exist (from earlier migration)
        $hasAllColumns = Schema::hasColumn('classes', 'code_salle') &&
                         Schema::hasColumn('classes', 'section_id') &&
                         Schema::hasColumn('classes', 'cycle_id');

        if ($hasAllColumns) {
            // All columns already exist, nothing to do
            return;
        }

        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'code_salle')) {
                $table->string('code_salle', 100)->nullable()->after('nom');
            }
            if (!Schema::hasColumn('classes', 'libelle_affichage')) {
                $table->string('libelle_affichage')->nullable()->after(Schema::hasColumn('classes', 'code_salle') ? 'code_salle' : 'nom');
            }
            if (!Schema::hasColumn('classes', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->after('niveau_id');
            }
            if (!Schema::hasColumn('classes', 'cycle_id')) {
                $table->unsignedBigInteger('cycle_id')->nullable()->after('section_id');
            }
            if (!Schema::hasColumn('classes', 'enseignant_titulaire_id')) {
                $table->unsignedBigInteger('enseignant_titulaire_id')->nullable()->after('capacite_max');
            }
            if (!Schema::hasColumn('classes', 'annee_scolaire_id')) {
                $table->unsignedBigInteger('annee_scolaire_id')->nullable()->after('enseignant_titulaire_id');
            }
            if (!Schema::hasColumn('classes', 'pays_id')) {
                $table->unsignedBigInteger('pays_id')->nullable()->after('annee_scolaire_id');
            }
            if (!Schema::hasColumn('classes', 'etat')) {
                $table->string('etat', 50)->nullable()->after('statut');
            }
        });

        // Add foreign keys only if they don't exist
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'section_id')) {
                try {
                    $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
            if (Schema::hasColumn('classes', 'cycle_id')) {
                try {
                    $table->foreign('cycle_id')->references('id')->on('cycles_enseignement')->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
            if (Schema::hasColumn('classes', 'enseignant_titulaire_id')) {
                try {
                    $table->foreign('enseignant_titulaire_id')->references('id')->on('users')->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
            if (Schema::hasColumn('classes', 'annee_scolaire_id')) {
                try {
                    $table->foreign('annee_scolaire_id')->references('id')->on('annees_scolaires')->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
            if (Schema::hasColumn('classes', 'pays_id')) {
                try {
                    $table->foreign('pays_id')->references('id')->on('pays')->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['pays_id']);
            $table->dropForeign(['annee_scolaire_id']);
            $table->dropForeign(['enseignant_titulaire_id']);
            $table->dropForeign(['cycle_id']);
            $table->dropForeign(['section_id']);

            $table->dropColumn([
                'code_salle', 'libelle_affichage', 'section_id', 'cycle_id',
                'enseignant_titulaire_id', 'annee_scolaire_id', 'pays_id', 'etat'
            ]);
        });
    }
};
