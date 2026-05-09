<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ecole_id');
            $table->string('categorie');
            $table->string('libelle');
            $table->unsignedBigInteger('montant_cents')->default(0);
            $table->date('date_depense');
            $table->unsignedBigInteger('facture_id')->nullable();
            $table->unsignedBigInteger('auteur_id');

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

            $table->foreign('ecole_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('facture_id')->references('id')->on('fichier')->nullOnDelete();
            $table->foreign('auteur_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
