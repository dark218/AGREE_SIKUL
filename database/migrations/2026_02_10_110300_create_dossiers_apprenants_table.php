<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dossiers_apprenants')) Schema::create('dossiers_apprenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apprenant_id')->unique()->constrained('apprenants')->cascadeOnDelete();
            $table->foreignId('extrait_naissance_id')->nullable()->constrained('fichier')->onDelete('cascade');
            $table->foreignId('certificat_residence_id')->nullable()->constrained('fichier')->onDelete('cascade');
            $table->foreignId('carnet_sante_id')->nullable()->constrained('fichier')->onDelete('cascade');
            $table->foreignId('dernier_bulletin_id')->nullable()->constrained('fichier')->onDelete('cascade');

            // Audit
            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->default('agree_sikul');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers_apprenants');
    }
};
