<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moyennes_matieres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bulletin_id');
            $table->unsignedBigInteger('matiere_id');
            $table->decimal('moyenne', 8, 2)->nullable();
            $table->decimal('coefficient', 5, 2)->default(1);
            $table->integer('rang')->nullable();
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

            $table->foreign('bulletin_id')->references('id')->on('bulletins')->cascadeOnDelete();
            $table->foreign('matiere_id')->references('id')->on('matieres')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moyennes_matieres');
    }
};
