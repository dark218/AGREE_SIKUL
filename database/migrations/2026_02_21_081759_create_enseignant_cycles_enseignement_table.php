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
        if (!Schema::hasTable('enseignant_cycles_enseignement')) Schema::hasTable('enseignant_cycles_enseignement') ? null : Schema::create('enseignant_cycles_enseignement', function (Blueprint $table) {
            $table->unsignedBigInteger('enseignant_id');
            $table->unsignedBigInteger('cycle_enseignement_id');

            $table->primary(['enseignant_id', 'cycle_enseignement_id']);

            $table->foreign('enseignant_id')->references('id')->on('enseignants')->onDelete('cascade');
            $table->foreign('cycle_enseignement_id')->references('id')->on('cycles_enseignement')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enseignant_cycles_enseignement');
    }
};
