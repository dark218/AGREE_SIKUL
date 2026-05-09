<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('categorie_id');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('fichier_id');
            $table->unsignedBigInteger('auteur_id');
            $table->json('cibles')->nullable();
            $table->timestamp('date_publication')->useCurrent();

            $table->timestamps();
            $table->softDeletes();

            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->nullable();
            $table->string('creation_hostname')->nullable();
            $table->string('modification_hostname')->nullable();
            $table->string('deletion_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();

            $table->index(['external_id']);
            $table->index(['source_system']);

            $table->foreign('categorie_id')->references('id')->on('categories_documents')->cascadeOnDelete();
            $table->foreign('fichier_id')->references('id')->on('fichier')->cascadeOnDelete();
            $table->foreign('auteur_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
