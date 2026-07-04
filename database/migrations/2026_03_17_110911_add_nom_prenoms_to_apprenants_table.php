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
        Schema::table('apprenants', function (Blueprint $table) {
            // Add columns that were referenced in entity but missing from original migration
            if (!Schema::hasColumn('apprenants', 'nom')) {
                $table->string('nom', 255)->nullable()->after('matricule');
            }
            if (!Schema::hasColumn('apprenants', 'prenoms')) {
                $table->string('prenoms', 255)->nullable()->after('nom');
            }
            if (!Schema::hasColumn('apprenants', 'email')) {
                $table->string('email', 255)->nullable();
            }
            if (!Schema::hasColumn('apprenants', 'telephone')) {
                $table->string('telephone', 20)->nullable();
            }
            if (!Schema::hasColumn('apprenants', 'telephone2')) {
                $table->string('telephone2', 20)->nullable();
            }
            if (!Schema::hasColumn('apprenants', 'whatsapp1')) {
                $table->string('whatsapp1', 20)->nullable();
            }
            if (!Schema::hasColumn('apprenants', 'whatsapp2')) {
                $table->string('whatsapp2', 20)->nullable();
            }
            if (!Schema::hasColumn('apprenants', 'adresse')) {
                $table->text('adresse')->nullable();
            }
            if (!Schema::hasColumn('apprenants', 'classe_id')) {
                $table->foreignId('classe_id')->nullable()->constrained('classes')->onDelete('set null');
            }
            if (!Schema::hasColumn('apprenants', 'section_id')) {
                $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
            }
            if (!Schema::hasColumn('apprenants', 'cycle_id')) {
                $table->foreignId('cycle_id')->nullable()->constrained('cycles_enseignement')->onDelete('set null');
            }
            if (!Schema::hasColumn('apprenants', 'ecole_id')) {
                $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            }
            if (!Schema::hasColumn('apprenants', 'campus_id')) {
                $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');
            }
            if (!Schema::hasColumn('apprenants', 'annee_scolaire_id')) {
                $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->onDelete('set null');
            }
            if (!Schema::hasColumn('apprenants', 'numero_inscription')) {
                $table->string('numero_inscription', 100)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apprenants', function (Blueprint $table) {
            $columns = ['nom', 'prenoms', 'email', 'telephone', 'telephone2', 'whatsapp1', 'whatsapp2', 'adresse', 'classe_id', 'section_id', 'cycle_id', 'ecole_id', 'campus_id', 'annee_scolaire_id', 'numero_inscription'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('apprenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
