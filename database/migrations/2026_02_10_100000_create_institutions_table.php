<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('institutions')) Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('nom', 255);
            $table->string('sigle', 50)->nullable();
            $table->enum('type', ['primaire', 'secondaire', 'superieur', 'formation', 'autre'])->default('autre');
            $table->string('statut_juridique', 100)->nullable();
            $table->string('numero_autorisation', 100)->nullable();
            $table->date('date_creation')->nullable();
            $table->foreignId('logo_id')->nullable()->constrained('fichier')->onDelete('cascade');
            $table->foreignId('cachet_id')->nullable()->constrained('fichier')->onDelete('cascade');
            $table->foreignId('directeur_general_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('email_principal', 255)->nullable();
            $table->string('telephone_principal', 20)->nullable();
            $table->string('site_web', 255)->nullable();
            $table->text('adresse_siege')->nullable();
            $table->foreignId('pays_id')->nullable()->constrained('pays')->onDelete('cascade');
            $table->string('devise_principale', 3)->nullable();
            $table->string('fuseau_horaire', 50)->nullable();
            $table->string('langue_principale', 10)->default('fr');
            $table->json('langues_secondaires')->nullable();
            $table->text('description')->nullable();
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();
            $table->json('valeurs')->nullable();
            $table->enum('statut', ['non_actif', 'actif', 'suspendu', 'bloque'])->default('actif');

            // Audit
            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->default('agree_sikul');
            $table->string('creation_hostname')->nullable();
            $table->string('modification_hostname')->nullable();
            $table->string('deletion_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('code');
            $table->index('statut');
            $table->index(['external_id']);
            $table->index(['source_system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
