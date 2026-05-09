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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('points_vente_id')->nullable()->constrained('points_vente')->onUpdate('cascade');
            $table->string('sku', 100)->comment("Ce champ stocke le code unique de l’article.");
            $table->string('nom', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('prix_cents')->comment("Ce champ stocke le prix unitaire de l’article, exprimé en centimes de la devise.(prix réel)");
            $table->string('devise', 3)->default('XOF');
            $table->integer('quantite_stock')->default(0);
            $table->integer('seuil_alert_stock')->default(0);
            $table->json('taxes_json')->nullable()->comment("Ce champ stocke les taxes applicables à l’article en format JSON.");
            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->default('smilpay');
            $table->string('creation_hostname')->nullable();
            $table->string('modification_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();
            $table->index(['external_id']);
            $table->index(['source_system']);
            $table->foreignId('deleted_by')
                ->nullable() // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
