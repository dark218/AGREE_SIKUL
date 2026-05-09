<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('apprenants', function (Blueprint $table) {
            // Birth location
            if (!Schema::hasColumn('apprenants', 'commune_naissance_id')) {
                $table->unsignedBigInteger('commune_naissance_id')->nullable()->after('lieu_naissance');
                $table->foreign('commune_naissance_id')->references('id')->on('communes')->nullOnDelete();
            }
            if (!Schema::hasColumn('apprenants', 'departement_naissance_id')) {
                $table->unsignedBigInteger('departement_naissance_id')->nullable()->after('commune_naissance_id');
                $table->foreign('departement_naissance_id')->references('id')->on('departements')->nullOnDelete();
            }
            if (!Schema::hasColumn('apprenants', 'region_naissance_id')) {
                $table->unsignedBigInteger('region_naissance_id')->nullable()->after('departement_naissance_id');
                $table->foreign('region_naissance_id')->references('id')->on('regions')->nullOnDelete();
            }
            if (!Schema::hasColumn('apprenants', 'pays_naissance_id')) {
                $table->unsignedBigInteger('pays_naissance_id')->nullable()->after('region_naissance_id');
                $table->foreign('pays_naissance_id')->references('id')->on('pays')->nullOnDelete();
            }

            // Academic structure
            if (!Schema::hasColumn('apprenants', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->after('classe_id');
                $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
            }
            if (!Schema::hasColumn('apprenants', 'cycle_id')) {
                $table->unsignedBigInteger('cycle_id')->nullable()->after('section_id');
                $table->foreign('cycle_id')->references('id')->on('cycles_enseignement')->nullOnDelete();
            }
            if (!Schema::hasColumn('apprenants', 'ecole_id')) {
                $table->unsignedBigInteger('ecole_id')->nullable()->after('cycle_id');
                $table->foreign('ecole_id')->references('id')->on('ecoles')->nullOnDelete();
            }
            if (!Schema::hasColumn('apprenants', 'campus_id')) {
                $table->unsignedBigInteger('campus_id')->nullable()->after('ecole_id');
                $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            }

            // Previous education
            if (!Schema::hasColumn('apprenants', 'ecole_precedente')) {
                $table->string('ecole_precedente')->nullable()->after('campus_id');
            }
            if (!Schema::hasColumn('apprenants', 'classe_precedente')) {
                $table->string('classe_precedente')->nullable()->after('ecole_precedente');
            }

            // Accommodation
            if (!Schema::hasColumn('apprenants', 'est_interne')) {
                $table->boolean('est_interne')->default(false)->after('classe_precedente');
            }
            if (!Schema::hasColumn('apprenants', 'batiment')) {
                $table->string('batiment')->nullable()->after('est_interne');
            }
            if (!Schema::hasColumn('apprenants', 'etage')) {
                $table->string('etage')->nullable()->after('batiment');
            }
            if (!Schema::hasColumn('apprenants', 'chambre')) {
                $table->string('chambre')->nullable()->after('etage');
            }
            if (!Schema::hasColumn('apprenants', 'numero_lit')) {
                $table->string('numero_lit')->nullable()->after('chambre');
            }

            // Family
            if (!Schema::hasColumn('apprenants', 'nom_pere')) {
                $table->string('nom_pere')->nullable()->after('numero_lit');
            }
            if (!Schema::hasColumn('apprenants', 'nom_mere')) {
                $table->string('nom_mere')->nullable()->after('nom_pere');
            }
            if (!Schema::hasColumn('apprenants', 'nom_tuteur')) {
                $table->string('nom_tuteur')->nullable()->after('nom_mere');
            }
            if (!Schema::hasColumn('apprenants', 'nom_responsable_legal')) {
                $table->string('nom_responsable_legal')->nullable()->after('nom_tuteur');
            }

            // Residence address
            if (!Schema::hasColumn('apprenants', 'quartier_id')) {
                $table->unsignedBigInteger('quartier_id')->nullable()->after('nom_responsable_legal');
                $table->foreign('quartier_id')->references('id')->on('quartiers')->nullOnDelete();
            }
            if (!Schema::hasColumn('apprenants', 'commune_residence_id')) {
                $table->unsignedBigInteger('commune_residence_id')->nullable()->after('quartier_id');
                $table->foreign('commune_residence_id')->references('id')->on('communes')->nullOnDelete();
            }
            if (!Schema::hasColumn('apprenants', 'departement_residence_id')) {
                $table->unsignedBigInteger('departement_residence_id')->nullable()->after('commune_residence_id');
                $table->foreign('departement_residence_id')->references('id')->on('departements')->nullOnDelete();
            }
            if (!Schema::hasColumn('apprenants', 'region_residence_id')) {
                $table->unsignedBigInteger('region_residence_id')->nullable()->after('departement_residence_id');
                $table->foreign('region_residence_id')->references('id')->on('regions')->nullOnDelete();
            }
            if (!Schema::hasColumn('apprenants', 'pays_residence_id')) {
                $table->unsignedBigInteger('pays_residence_id')->nullable()->after('region_residence_id');
                $table->foreign('pays_residence_id')->references('id')->on('pays')->nullOnDelete();
            }
            if (!Schema::hasColumn('apprenants', 'arrondissement')) {
                $table->string('arrondissement')->nullable()->after('pays_residence_id');
            }
            if (!Schema::hasColumn('apprenants', 'ville')) {
                $table->string('ville')->nullable()->after('arrondissement');
            }
            if (!Schema::hasColumn('apprenants', 'code_postal')) {
                $table->string('code_postal')->nullable()->after('ville');
            }
            if (!Schema::hasColumn('apprenants', 'boite_postal')) {
                $table->string('boite_postal')->nullable()->after('code_postal');
            }

            // Contact information
            if (!Schema::hasColumn('apprenants', 'telephone2')) {
                $table->string('telephone2', 20)->nullable()->after('telephone');
            }
            if (!Schema::hasColumn('apprenants', 'whatsapp1')) {
                $table->string('whatsapp1', 20)->nullable()->after('telephone2');
            }
            if (!Schema::hasColumn('apprenants', 'whatsapp2')) {
                $table->string('whatsapp2', 20)->nullable()->after('whatsapp1');
            }

            // School entry/exit dates
            if (!Schema::hasColumn('apprenants', 'date_entree_ecole')) {
                $table->date('date_entree_ecole')->nullable()->after('whatsapp2');
            }
            if (!Schema::hasColumn('apprenants', 'date_depart_ecole')) {
                $table->date('date_depart_ecole')->nullable()->after('date_entree_ecole');
            }
            if (!Schema::hasColumn('apprenants', 'motif_depart_ecole')) {
                $table->text('motif_depart_ecole')->nullable()->after('date_depart_ecole');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback not needed
    }
};
