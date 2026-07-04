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
        if (!Schema::hasTable('enseignant_classes')) Schema::hasTable('enseignant_classes') ? null : Schema::create('enseignant_classes', function (Blueprint $table) {
            $table->unsignedBigInteger('enseignant_id');
            $table->unsignedBigInteger('classe_id');

            $table->primary(['enseignant_id', 'classe_id']);

            $table->foreign('enseignant_id')->references('id')->on('enseignants')->onDelete('cascade');
            $table->foreign('classe_id')->references('id')->on('classes')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enseignant_classes');
    }
};
