<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annonces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auteur_id');
            $table->string('titre');
            $table->longText('contenu');
            $table->json('cibles')->nullable();
            $table->timestamp('date_publication')->useCurrent();
            $table->timestamp('date_expiration')->nullable();
            $table->enum('statut', ['active', 'inactive', 'archive'])->default('active');

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

            $table->foreign('auteur_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annonces');
    }
};
