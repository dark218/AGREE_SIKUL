<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('categorie_id');
            $table->unsignedBigInteger('ecole_id');
            $table->string('nom');
            $table->string('reference')->nullable()->unique();
            $table->date('date_acquisition')->nullable();
            $table->unsignedBigInteger('prix_cents')->default(0);
            $table->enum('etat', ['excellent', 'bon', 'moyen', 'mauvais', 'non_fonctionnel'])->default('bon');
            $table->string('localisation')->nullable();

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

            $table->foreign('categorie_id')->references('id')->on('categories_equipements')->cascadeOnDelete();
            $table->foreign('ecole_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipements');
    }
};
