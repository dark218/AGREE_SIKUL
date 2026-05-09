<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('apprenant_id');
            $table->unsignedBigInteger('classe_id');
            $table->enum('periode', ['trimestre1', 'trimestre2', 'trimestre3', 'semestre1', 'semestre2', 'annuel'])->default('trimestre1');
            $table->unsignedBigInteger('annee_scolaire_id');
            $table->decimal('moyenne_generale', 8, 2)->nullable();
            $table->integer('rang')->nullable();
            $table->enum('decision_conseil', ['admis', 'ajourne', 'en_attente'])->default('en_attente');
            $table->text('appreciation_generale')->nullable();

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

            $table->foreign('apprenant_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('annee_scolaire_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletins');
    }
};
