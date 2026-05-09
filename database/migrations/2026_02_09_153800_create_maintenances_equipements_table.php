<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances_equipements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipement_id');
            $table->date('date_maintenance');
            $table->enum('type_maintenance', ['preventive', 'corrective', 'inspection'])->default('corrective');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('cout_cents')->default(0);
            $table->unsignedBigInteger('technicien_id')->nullable();

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

            $table->foreign('equipement_id')->references('id')->on('equipements')->cascadeOnDelete();
            $table->foreign('technicien_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances_equipements');
    }
};
