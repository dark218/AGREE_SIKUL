<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sous-fonctionnalité "Liste" de la Bibliothèque : les bibliothèques (lieux).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bibliotheque_structures')) {
            return;
        }

        Schema::create('bibliotheque_structures', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->nullable();
            $table->string('libelle', 255);
            $table->string('localisation', 255)->nullable();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->string('responsable', 255)->nullable();
            $table->enum('statut_disponibilite', ['disponible', 'indisponible', 'maintenance'])->default('disponible');
            $table->enum('etat', ['actif', 'inactif'])->default('actif');

            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('source_system')->default('agree_sikul');

            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('etat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bibliotheque_structures');
    }
};
