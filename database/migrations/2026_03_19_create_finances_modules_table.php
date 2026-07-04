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
        // Table Groupes de Comptes
        if (!Schema::hasTable('groupes_comptes')) Schema::hasTable('groupes_comptes') ? null : Schema::create('groupes_comptes', function (Blueprint $table) {
            $table->id();
            $table->string('code_groupe')->nullable()->unique();
            $table->string('libelle_groupes')->nullable();
            $table->integer('nombre_comptes')->nullable();
            $table->text('liste_comptes')->nullable();
            $table->text('description')->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('etat');
        });

        // Table Plan des Comptes
        if (!Schema::hasTable('plan_comptes')) Schema::hasTable('plan_comptes') ? null : Schema::create('plan_comptes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupe_comptes_id')->nullable()->constrained('groupes_comptes')->onDelete('cascade');
            $table->string('numero_compte')->nullable()->unique();
            $table->string('libelle_compte')->nullable();
            $table->string('libelle_court')->nullable();
            $table->foreignId('compte_parent_id')->nullable()->references('id')->on('plan_comptes')->onDelete('set null');
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['groupe_comptes_id', 'etat']);
        });

        // Table Lignes de Recettes
        if (!Schema::hasTable('lignes_recettes')) Schema::hasTable('lignes_recettes') ? null : Schema::create('lignes_recettes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupe_compte_id')->nullable()->constrained('groupes_comptes')->onDelete('set null');
            $table->string('code')->nullable()->unique();
            $table->string('libelle')->nullable();
            $table->string('compte_comptable')->nullable();
            $table->decimal('montant_estime', 15, 2)->nullable();
            $table->decimal('montant_reel', 15, 2)->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['groupe_compte_id', 'etat']);
        });

        // Table Lignes de Dépenses
        if (!Schema::hasTable('lignes_depenses')) Schema::hasTable('lignes_depenses') ? null : Schema::create('lignes_depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupe_compte_id')->nullable()->constrained('groupes_comptes')->onDelete('set null');
            $table->string('code')->nullable()->unique();
            $table->string('libelle')->nullable();
            $table->string('compte_comptable')->nullable();
            $table->decimal('montant_estime', 15, 2)->nullable();
            $table->decimal('montant_reel', 15, 2)->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['groupe_compte_id', 'etat']);
        });

        // Table Postes de Recettes
        if (!Schema::hasTable('postes_recettes')) Schema::hasTable('postes_recettes') ? null : Schema::create('postes_recettes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ligne_recette_id')->nullable()->constrained('lignes_recettes')->onDelete('set null');
            $table->string('code')->nullable()->unique();
            $table->string('libelle')->nullable();
            $table->decimal('montant_estime', 15, 2)->nullable();
            $table->decimal('montant_reel', 15, 2)->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['ligne_recette_id', 'etat']);
        });

        // Table Postes de Dépenses
        if (!Schema::hasTable('postes_depenses')) Schema::hasTable('postes_depenses') ? null : Schema::create('postes_depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ligne_depense_id')->nullable()->constrained('lignes_depenses')->onDelete('set null');
            $table->string('code')->nullable()->unique();
            $table->string('libelle')->nullable();
            $table->decimal('montant_estime', 15, 2)->nullable();
            $table->decimal('montant_reel', 15, 2)->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['ligne_depense_id', 'etat']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postes_depenses');
        Schema::dropIfExists('postes_recettes');
        Schema::dropIfExists('lignes_depenses');
        Schema::dropIfExists('lignes_recettes');
        Schema::dropIfExists('groupes_comptes');
    }
};
