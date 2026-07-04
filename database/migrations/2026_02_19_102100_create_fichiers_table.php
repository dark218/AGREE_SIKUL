<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('fichiers')) Schema::hasTable('fichiers') ? null : Schema::create('fichiers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->string('chemin_fichier')->nullable();
            $table->string('type_fichier')->nullable();
            $table->integer('taille_fichier')->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fichiers');
    }
};
