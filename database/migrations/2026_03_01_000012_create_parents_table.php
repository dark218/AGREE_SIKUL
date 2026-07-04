<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('parents')) Schema::hasTable('parents') ? null : Schema::create('parents', function (Blueprint $table) {
            $table->id();

            // Informations de l'apprenant
            $table->foreignId('apprenant_id')->nullable()->constrained('apprenants')->onDelete('set null');
            $table->foreignId('classe_id')->nullable()->constrained('classes')->onDelete('set null');
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('set null');
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');

            // Informations du père
            $table->string('pere_nom')->nullable();
            $table->string('pere_prenoms')->nullable();
            $table->string('pere_nom_complet')->nullable();
            $table->string('pere_profession')->nullable();
            $table->string('pere_organisation_travail')->nullable();
            $table->string('pere_ville_travail')->nullable();
            $table->string('pere_pays_travail')->nullable();
            $table->text('pere_adresse_residence')->nullable();
            $table->string('pere_quartier')->nullable();
            $table->string('pere_commune')->nullable();
            $table->string('pere_departement')->nullable();
            $table->string('pere_region')->nullable();
            $table->string('pere_code_postal')->nullable();
            $table->string('pere_boite_postal')->nullable();
            $table->string('pere_telephone_1')->nullable();
            $table->string('pere_telephone_2')->nullable();
            $table->string('pere_whatsapp_1')->nullable();
            $table->string('pere_whatsapp_2')->nullable();
            $table->string('pere_email_1')->nullable();
            $table->string('pere_email_2')->nullable();

            // Informations de la mère
            $table->string('mere_nom')->nullable();
            $table->string('mere_prenoms')->nullable();
            $table->string('mere_nom_complet')->nullable();
            $table->string('mere_profession')->nullable();
            $table->string('mere_organisation_travail')->nullable();
            $table->string('mere_ville_travail')->nullable();
            $table->string('mere_pays_travail')->nullable();
            $table->text('mere_adresse_residence')->nullable();
            $table->string('mere_quartier')->nullable();
            $table->string('mere_commune')->nullable();
            $table->string('mere_departement')->nullable();
            $table->string('mere_region')->nullable();
            $table->string('mere_code_postal')->nullable();
            $table->string('mere_boite_postal')->nullable();
            $table->string('mere_telephone_1')->nullable();
            $table->string('mere_telephone_2')->nullable();
            $table->string('mere_whatsapp_1')->nullable();
            $table->string('mere_whatsapp_2')->nullable();
            $table->string('mere_email_1')->nullable();
            $table->string('mere_email_2')->nullable();

            // Tuteur 1
            $table->string('tuteur1_nom')->nullable();
            $table->string('tuteur1_prenoms')->nullable();
            $table->string('tuteur1_nom_complet')->nullable();
            $table->string('tuteur1_profession')->nullable();
            $table->string('tuteur1_organisation_travail')->nullable();
            $table->string('tuteur1_ville_travail')->nullable();
            $table->string('tuteur1_pays_travail')->nullable();
            $table->text('tuteur1_adresse_residence')->nullable();
            $table->string('tuteur1_quartier')->nullable();
            $table->string('tuteur1_commune')->nullable();
            $table->string('tuteur1_arrondissement')->nullable();
            $table->string('tuteur1_ville')->nullable();
            $table->string('tuteur1_departement')->nullable();
            $table->string('tuteur1_region')->nullable();
            $table->string('tuteur1_pays')->nullable();
            $table->string('tuteur1_code_postal')->nullable();
            $table->string('tuteur1_boite_postal')->nullable();
            $table->string('tuteur1_telephone_1')->nullable();
            $table->string('tuteur1_telephone_2')->nullable();
            $table->string('tuteur1_email')->nullable();
            $table->string('tuteur1_whatsapp_1')->nullable();
            $table->string('tuteur1_whatsapp_2')->nullable();

            // Tuteur 2
            $table->string('tuteur2_nom')->nullable();
            $table->string('tuteur2_prenoms')->nullable();
            $table->string('tuteur2_nom_complet')->nullable();
            $table->string('tuteur2_profession')->nullable();
            $table->string('tuteur2_organisation_travail')->nullable();
            $table->string('tuteur2_ville_travail')->nullable();
            $table->string('tuteur2_pays_travail')->nullable();
            $table->text('tuteur2_adresse_residence')->nullable();
            $table->string('tuteur2_quartier')->nullable();
            $table->string('tuteur2_commune')->nullable();
            $table->string('tuteur2_arrondissement')->nullable();
            $table->string('tuteur2_ville')->nullable();
            $table->string('tuteur2_departement')->nullable();
            $table->string('tuteur2_region')->nullable();
            $table->string('tuteur2_pays')->nullable();
            $table->string('tuteur2_code_postal')->nullable();
            $table->string('tuteur2_boite_postal')->nullable();
            $table->string('tuteur2_telephone_1')->nullable();
            $table->string('tuteur2_telephone_2')->nullable();
            $table->string('tuteur2_email')->nullable();
            $table->string('tuteur2_whatsapp_1')->nullable();
            $table->string('tuteur2_whatsapp_2')->nullable();

            // Audit
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('apprenant_id');
            $table->index('ecole_id');
            $table->index('etat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parents');
    }
};
