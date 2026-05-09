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
        if (!Schema::hasTable('configurations_finances')) Schema::create('configurations_finances', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Code unique de la configuration');
            $table->string('libelle')->comment('Libellé de la configuration');
            $table->longText('valeur')->comment('Valeur de la configuration');
            $table->enum('type', ['texte', 'monetaire', 'pourcentage', 'booleen', 'nombre'])->default('texte')->comment('Type de configuration');
            $table->text('description')->nullable()->comment('Description de la configuration');
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations_finances');
    }
};
