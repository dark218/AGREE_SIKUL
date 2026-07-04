<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('passages')) Schema::hasTable('passages') ? null : Schema::create('passages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
            $table->foreignId('cycle_enseignement_id')->nullable()->constrained('cycles_enseignement')->onDelete('set null');
            $table->foreignId('niveau_id')->constrained('niveaux')->onDelete('cascade');
            $table->foreignId('niveau_superieur_id')->nullable()->constrained('niveaux')->onDelete('set null');
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['section_id', 'niveau_id']);
            $table->index('cycle_enseignement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passages');
    }
};
