<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('justificatifs_absences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('absence_id');
            $table->unsignedBigInteger('fichier_id');
            $table->text('commentaire')->nullable();
            $table->unsignedBigInteger('valide_par')->nullable();
            $table->timestamp('valide_at')->nullable();

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

            $table->foreign('absence_id')->references('id')->on('absences_apprenants')->cascadeOnDelete();
            $table->foreign('fichier_id')->references('id')->on('fichier')->cascadeOnDelete();
            $table->foreign('valide_par')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justificatifs_absences');
    }
};
