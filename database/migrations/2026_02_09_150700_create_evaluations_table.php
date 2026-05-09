<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cours_id');
            $table->enum('type_evaluation', ['controle_continu', 'devoir', 'examen', 'projet', 'oral'])->default('controle_continu');
            $table->string('titre');
            $table->date('date_evaluation');
            $table->integer('duree')->nullable(); // en minutes
            $table->decimal('coefficient', 5, 2)->default(1);
            $table->decimal('note_sur', 8, 2)->default(20);

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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
