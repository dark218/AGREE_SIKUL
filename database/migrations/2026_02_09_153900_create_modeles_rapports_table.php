<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modeles_rapports', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('type', ['pdf', 'excel', 'csv', 'html'])->default('pdf');
            $table->json('parametres')->nullable();

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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modeles_rapports');
    }
};
