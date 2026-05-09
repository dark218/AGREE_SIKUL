<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bibliotheques', function (Blueprint $table) {
            $table->id();
            $table->string('sujet')->nullable();
            $table->string('langue')->nullable();
            $table->string('niveau')->nullable();
            $table->string('type_manuel')->nullable();
            $table->string('titre_manuel')->nullable();
            $table->text('auteurs')->nullable();
            $table->string('editeurs')->nullable();
            $table->integer('annee_edition')->nullable();
            $table->integer('quantite')->default(0);
            $table->integer('sorties')->default(0);
            $table->integer('disponibles')->default(0);
            $table->enum('etat', ['actif', 'inactif'])->default('actif');

            $table->timestamps();
            $table->softDeletes();

            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();

            $table->index(['sujet']);
            $table->index(['type_manuel']);
            $table->index(['etat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bibliotheques');
    }
};
