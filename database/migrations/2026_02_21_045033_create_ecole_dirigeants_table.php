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
        if (!Schema::hasTable('ecole_dirigeants')) Schema::hasTable('ecole_dirigeants') ? null : Schema::create('ecole_dirigeants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ecole_id');
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('nom_restituer')->nullable();
            $table->string('fonction')->nullable();
            $table->integer('ordre')->default(0);
            $table->timestamps();

            // Relation
            $table->foreign('ecole_id')->references('id')->on('ecoles')->onDelete('cascade');
            $table->index('ecole_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecole_dirigeants');
    }
};
