<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devoirs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cours_id');
            $table->string('titre');
            $table->longText('description')->nullable();
            $table->date('date_donnee');
            $table->date('date_limite');
            $table->unsignedBigInteger('fichier_enonce_id')->nullable();

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

            $table->foreign('cours_id')->references('id')->on('cours')->cascadeOnDelete();
            $table->foreign('fichier_enonce_id')->references('id')->on('fichier')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devoirs');
    }
};
