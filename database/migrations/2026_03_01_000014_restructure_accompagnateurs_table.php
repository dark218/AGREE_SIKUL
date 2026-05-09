<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old table and create a new one with the correct structure
        Schema::dropIfExists('accompagnateurs');

        if (!Schema::hasTable('accompagnateurs')) Schema::create('accompagnateurs', function (Blueprint $table) {
            $table->id();

            // School Information
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('set null');
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');

            // ACCOMPAGNANT 1
            $table->string('accompagnant1_civilite')->nullable();
            $table->string('accompagnant1_nom')->nullable();
            $table->string('accompagnant1_prenoms')->nullable();
            $table->string('accompagnant1_nom_complet')->nullable();
            $table->string('accompagnant1_lien')->nullable();
            $table->foreignId('accompagnant1_photo_id')->nullable()->constrained('fichiers')->onDelete('set null');

            // ACCOMPAGNANT 2
            $table->string('accompagnant2_civilite')->nullable();
            $table->string('accompagnant2_nom')->nullable();
            $table->string('accompagnant2_prenoms')->nullable();
            $table->string('accompagnant2_nom_complet')->nullable();
            $table->string('accompagnant2_lien')->nullable();
            $table->foreignId('accompagnant2_photo_id')->nullable()->constrained('fichiers')->onDelete('set null');

            // ACCOMPAGNANT 3
            $table->string('accompagnant3_civilite')->nullable();
            $table->string('accompagnant3_nom')->nullable();
            $table->string('accompagnant3_prenoms')->nullable();
            $table->string('accompagnant3_nom_complet')->nullable();
            $table->string('accompagnant3_lien')->nullable();
            $table->foreignId('accompagnant3_photo_id')->nullable()->constrained('fichiers')->onDelete('set null');

            // Audit
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('ecole_id');
            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('etat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accompagnateurs');
    }
};
