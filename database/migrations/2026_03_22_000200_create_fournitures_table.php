<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fournitures')) Schema::create('fournitures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('categorie_fourniture_id');
            $table->string('libelle');
            $table->string('code')->nullable()->unique();
            $table->unsignedInteger('quantite')->default(0);
            $table->unsignedBigInteger('prix_unitaire_cents')->default(0);
            $table->string('fournisseur')->nullable();
            $table->date('date_acquisition')->nullable();
            $table->enum('statut', ['disponible', 'rupture', 'commandee'])->default('disponible');
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

            $table->foreign('categorie_fourniture_id')->references('id')->on('categories_fournitures')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fournitures');
    }
};
