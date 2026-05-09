<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('modele_rapport_id');
            $table->string('titre');
            $table->json('parametres_utilises')->nullable();
            $table->unsignedBigInteger('fichier_id')->nullable();
            $table->unsignedBigInteger('genere_par');
            $table->timestamp('date_generation')->useCurrent();

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

            $table->foreign('modele_rapport_id')->references('id')->on('modeles_rapports')->cascadeOnDelete();
            $table->foreign('fichier_id')->references('id')->on('fichier')->nullOnDelete();
            $table->foreign('genere_par')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapports');
    }
};
