<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accompagnateurs')) Schema::create('accompagnateurs', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('nom')->nullable();
            $table->string('prenoms')->nullable();
            $table->string('nom_complet')->nullable();
            $table->string('matricule')->unique()->nullable();
            $table->string('numero_identite')->unique()->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->date('date_naissance')->nullable();
            $table->enum('sexe', ['masculin', 'feminin'])->nullable();
            $table->string('nationalite')->nullable();
            $table->enum('situation_familiale', ['celibataire', 'marie', 'divorce', 'veuf'])->nullable();

            // Contact Information
            $table->string('telephone_principal')->nullable();
            $table->string('telephone_secondaire')->nullable();
            $table->string('email_principal')->nullable();
            $table->string('email_secondaire')->nullable();
            $table->string('whatsapp')->nullable();

            // Residential Address
            $table->text('adresse_residence')->nullable();
            $table->string('quartier')->nullable();
            $table->string('commune')->nullable();
            $table->string('arrondissement')->nullable();
            $table->string('departement')->nullable();
            $table->string('region')->nullable();
            $table->string('code_postal')->nullable();
            $table->string('boite_postale')->nullable();
            $table->string('pays_residence')->nullable();

            // Professional Information
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('set null');
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->onDelete('set null');
            $table->string('poste')->nullable();
            $table->string('fonction')->nullable();
            $table->date('date_embauche')->nullable();
            $table->date('date_fin_contrat')->nullable();
            $table->enum('type_contrat', ['cdi', 'cdd', 'stagiaire', 'vacataire'])->nullable();
            $table->string('diplome_principal')->nullable();
            $table->string('specialisation')->nullable();
            $table->string('experience_professionnelle')->nullable();

            // Emergency Contact
            $table->string('contact_urgence_nom')->nullable();
            $table->string('contact_urgence_prenoms')->nullable();
            $table->string('contact_urgence_telephone')->nullable();
            $table->string('contact_urgence_relation')->nullable();

            // Banking Information
            $table->string('nom_banque')->nullable();
            $table->string('compte_bancaire')->nullable();
            $table->string('code_agence')->nullable();
            $table->string('nom_titulaire_compte')->nullable();

            // Additional Information
            $table->text('notes')->nullable();
            $table->boolean('validation_documents')->default(false);
            $table->string('numero_fiscal')->nullable();
            $table->string('numero_securite_sociale')->nullable();

            // Audit
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('ecole_id');
            $table->index('etat');
            $table->index('matricule');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accompagnateurs');
    }
};
