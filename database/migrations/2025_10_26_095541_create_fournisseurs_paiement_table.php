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
        if (Schema::hasTable('fournisseurs_paiement')) return;
        Schema::create('fournisseurs_paiement', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code')->unique();
            $table->enum('type', ['mm','bank','microfinance','aggregator','card'])->default('mm');
            // TODO: Add foreign key to pays_devises once table is created
            $table->unsignedBigInteger('pays_devise_id')->nullable();
            $table->foreignId('logo_id')->nullable()->constrained('fichier')->cascadeOnDelete();
            $table->text('config')->nullable(); // JSON chiffré si possible
            $table->text('settlement_config')->nullable();
            $table->enum('statut', ['actif','inactif'])->default('actif');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fournisseurs_paiement');
    }
};
