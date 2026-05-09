<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ouvrages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bibliotheque_id');
            $table->string('titre');
            $table->string('auteur');
            $table->string('isbn')->nullable()->unique();
            $table->string('editeur')->nullable();
            $table->integer('annee_publication')->nullable();
            $table->string('categorie')->nullable();
            $table->integer('nombre_exemplaires')->default(1);

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

            $table->foreign('bibliotheque_id')->references('id')->on('bibliotheques')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ouvrages');
    }
};
