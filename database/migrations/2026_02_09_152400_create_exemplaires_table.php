<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exemplaires', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ouvrage_id');
            $table->string('code_exemplaire')->unique();
            $table->enum('etat', ['excellent', 'bon', 'moyen', 'mauvais', 'deteriore'])->default('bon');
            $table->enum('statut', ['disponible', 'emprunte', 'reserve', 'non_disponible'])->default('disponible');

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

            $table->foreign('ouvrage_id')->references('id')->on('ouvrages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exemplaires');
    }
};
