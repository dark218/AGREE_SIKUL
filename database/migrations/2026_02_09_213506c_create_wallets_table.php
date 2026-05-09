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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marchand_id')->constrained("marchands")->onDelete("cascade")->nullable();
            $table->foreignId('pointvente_id')->constrained("points_vente")->onDelete("cascade")->nullable();
            $table->foreignId('devise_id')->constrained('devises')->onDelete("cascade");
            $table->enum('type_compte',["marchand","pointvente","caisse","agent","system"]);
            $table->enum('statut',["actif","bloque","ferme"])->default("actif");
            $table->decimal('solde', 10, 2)->default(0);
            $table->decimal('solde_disponible', 10, 2)->default(0);
            $table->decimal('solde_en_attente', 10, 2)->default(0);
            $table->decimal('solde_commission', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
