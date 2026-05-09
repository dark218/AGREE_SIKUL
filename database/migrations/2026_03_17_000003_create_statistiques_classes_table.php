<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('statistiques_classes')) Schema::create('statistiques_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classe_id')->nullable()->constrained('classes')->onDelete('set null');
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('set null');
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');
            $table->integer('nombre_inscrits')->nullable();
            $table->integer('nombre_filles')->nullable();
            $table->integer('nombre_garcons')->nullable();
            $table->integer('nombre_enseignants')->nullable();
            $table->integer('nombre_enseignants_permanent')->nullable();
            $table->integer('nombre_enseignants_vacataires')->nullable();
            $table->string('enseignant_referent')->nullable();
            $table->text('produits_ecole')->nullable();
            $table->text('services_offerts')->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('ecole_id');
            $table->index('classe_id');
            $table->index('etat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistiques_classes');
    }
};
