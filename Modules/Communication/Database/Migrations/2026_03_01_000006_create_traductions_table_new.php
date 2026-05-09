<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('traductions')) Schema::create('traductions', function (Blueprint $table) {
            $table->id();
            $table->string('code_fr')->unique();
            $table->text('intitule_fr');
            $table->string('code_en')->unique();
            $table->text('intitule_en');
            $table->string('groupe')->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('groupe');
            $table->index('etat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traductions');
    }
};
