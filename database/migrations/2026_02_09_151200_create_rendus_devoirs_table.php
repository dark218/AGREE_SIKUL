<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rendus_devoirs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('devoir_id');
            $table->unsignedBigInteger('apprenant_id');
            $table->timestamp('date_rendu')->nullable();
            $table->unsignedBigInteger('fichier_rendu_id')->nullable();
            $table->decimal('note', 8, 2)->nullable();
            $table->text('appreciation')->nullable();

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

            $table->foreign('devoir_id')->references('id')->on('devoirs')->cascadeOnDelete();
            $table->foreign('apprenant_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('fichier_rendu_id')->references('id')->on('fichier')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendus_devoirs');
    }
};
