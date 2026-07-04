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
        Schema::table('evaluations', function (Blueprint $table) {
            // Add classe_id and matiere_id columns
            if (!Schema::hasColumn('evaluations', 'classe_id')) {
                $table->unsignedBigInteger('classe_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('evaluations', 'matiere_id')) {
                $table->unsignedBigInteger('matiere_id')->nullable()->after('classe_id');
            }

            // Add code column
            if (!Schema::hasColumn('evaluations', 'code')) {
                $table->string('code')->nullable()->after('matiere_id');
            }

            // Add statut column
            if (!Schema::hasColumn('evaluations', 'statut')) {
                $table->enum('statut', ['actif', 'inactif'])->default('actif');
            }

            // Add foreign keys
            if (Schema::hasColumn('evaluations', 'classe_id')) {
                try {
                    $table->foreign('classe_id')->references('id')->on('classes')->cascadeOnDelete();
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }

            if (Schema::hasColumn('evaluations', 'matiere_id')) {
                try {
                    $table->foreign('matiere_id')->references('id')->on('matieres')->cascadeOnDelete();
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
        Schema::table('evaluations', function (Blueprint $table) {
            // Drop foreign keys if they exist
            try {
                $table->dropForeignKey(['classe_id']);
            } catch (\Exception $e) {}
            try {
                $table->dropForeignKey(['matiere_id']);
            } catch (\Exception $e) {}

            // Drop columns
            $table->dropColumn(['classe_id', 'matiere_id', 'code', 'statut']);
        });
    }
};
