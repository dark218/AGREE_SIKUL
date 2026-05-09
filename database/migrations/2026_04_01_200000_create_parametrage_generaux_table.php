<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('parametrage_generaux')) Schema::create('parametrage_generaux', function (Blueprint $table) {
            $table->id();
            $table->string('nom_etablissement')->default('AGREE SIKUL');
            $table->string('slogan')->nullable();
            $table->string('sigle')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('logo_login_path')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('telephone2')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('site_web')->nullable();
            $table->string('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('pays')->nullable();
            $table->string('code_postal')->nullable();
            $table->string('boite_postale')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('instagram')->nullable();
            $table->string('youtube')->nullable();
            $table->string('numero_autorisation')->nullable();
            $table->string('registre_commerce')->nullable();
            $table->string('numero_fiscal')->nullable();
            $table->string('couleur_primaire')->default('#0B5697');
            $table->string('couleur_secondaire')->default('#E5590C');
            $table->string('couleur_accent')->default('#0FBCAF');
            $table->string('devise')->default('FCFA');
            $table->string('langue_par_defaut')->default('fr');
            $table->string('fuseau_horaire')->default('Africa/Abidjan');
            $table->year('annee_scolaire_courante')->nullable();
            $table->timestamps();
        });

        \DB::table('parametrage_generaux')->insert([
            'nom_etablissement' => 'AGREE SIKUL',
            'slogan' => 'Plateforme de gestion scolaire',
            'email' => 'contact@agreesikul.com',
            'logo_path' => 'images/image.png',
            'couleur_primaire' => '#0B5697',
            'couleur_secondaire' => '#E5590C',
            'couleur_accent' => '#0FBCAF',
            'devise' => 'FCFA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('parametrage_generaux');
    }
};
