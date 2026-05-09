<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorie_apprenants', function (Blueprint $table) {
            // Drop old foreign key first (before dropping index)
            if (Schema::hasColumn('categorie_apprenants', 'pays_id')) {
                try {
                    $table->dropConstrainedForeignId('pays_id');
                } catch (\Exception $e) {
                    // Foreign key might not exist, try dropping column directly
                    if (Schema::hasColumn('categorie_apprenants', 'pays_id')) {
                        $table->dropColumn('pays_id');
                    }
                }
            }

            // Drop unused columns if they exist
            if (Schema::hasColumn('categorie_apprenants', 'taux_reduction')) {
                $table->dropColumn('taux_reduction');
            }
            if (Schema::hasColumn('categorie_apprenants', 'priorite_inscription')) {
                $table->dropColumn('priorite_inscription');
            }
            if (Schema::hasColumn('categorie_apprenants', 'type_categorie')) {
                $table->dropColumn('type_categorie');
            }

            // Add ecole_id
            if (!Schema::hasColumn('categorie_apprenants', 'ecole_id')) {
                $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            }

            // Add new index
            if (!Schema::hasIndex('categorie_apprenants', 'categorie_apprenants_ecole_id_etat_index')) {
                $table->index(['ecole_id', 'etat']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('categorie_apprenants', function (Blueprint $table) {
            // Restore old structure
            if (!Schema::hasColumn('categorie_apprenants', 'pays_id')) {
                $table->foreignId('pays_id')->constrained('pays')->onDelete('cascade');
                $table->decimal('taux_reduction', 5, 2)->nullable();
                $table->integer('priorite_inscription')->nullable();
                $table->string('type_categorie')->nullable();
            }

            if (Schema::hasColumn('categorie_apprenants', 'ecole_id')) {
                try {
                    $table->dropIndex(['ecole_id', 'etat']);
                } catch (\Exception $e) {
                    // Index might not exist
                }
                try {
                    $table->dropConstrainedForeignId('ecole_id');
                } catch (\Exception $e) {
                    // Foreign key might not exist
                    if (Schema::hasColumn('categorie_apprenants', 'ecole_id')) {
                        $table->dropColumn('ecole_id');
                    }
                }
            }

            $table->index(['pays_id', 'etat']);
        });
    }
};
