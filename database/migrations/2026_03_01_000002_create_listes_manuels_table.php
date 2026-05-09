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
        if (!Schema::hasTable('listes_manuels')) Schema::create('listes_manuels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->cascadeOnDelete();
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->cascadeOnDelete();
            $table->string('type_manuel')->nullable();
            $table->string('titre_manuel');
            $table->text('auteurs')->nullable();
            $table->string('editeur')->nullable();
            $table->integer('annee_edition')->nullable();
            $table->foreignId('pays_id')->nullable()->constrained('pays')->cascadeOnDelete();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');

            $table->timestamps();
            $table->softDeletes();

            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->nullable();
            $table->string('creation_hostname')->nullable();
            $table->string('modification_hostname')->nullable();
            $table->string('deletion_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();

            $table->index(['external_id']);
            $table->index(['source_system']);
            $table->index(['annee_scolaire_id', 'ecole_id', 'section_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listes_manuels');
    }
};
