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
        if (!Schema::hasTable('fournitures')) Schema::create('fournitures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_fourniture_id')->constrained('categories_fournitures')->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('libelle');
            $table->integer('quantite')->default(0);
            $table->integer('prix_unitaire_cents')->nullable(); // Prix en centimes
            $table->string('fournisseur')->nullable();
            $table->date('date_acquisition')->nullable();
            $table->enum('statut', ['disponible', 'rupture', 'commandee'])->default('disponible');
            $table->string('localisation')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fournitures');
    }
};
