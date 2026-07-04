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
        // idempotence guard
        if (!Schema::hasTable('salles')) Schema::hasTable('salles') ? null : Schema::create('salles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('libelle');
            $table->integer('capacite')->default(0)->nullable();
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salles');
    }
};
