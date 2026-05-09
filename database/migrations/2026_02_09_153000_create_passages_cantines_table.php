<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passages_cantines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inscription_cantine_id');
            $table->date('date_passage');
            $table->time('heure_passage')->nullable();

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

            $table->foreign('inscription_cantine_id')->references('id')->on('inscriptions_cantines')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passages_cantines');
    }
};
