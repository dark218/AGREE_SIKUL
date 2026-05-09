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
        if (Schema::hasTable('ecoles')) if (Schema::hasTable('ecoles')) Schema::table('ecoles', function (Blueprint $table) {
            // Adresse et localisation
            $table->text('adresse_siege')->nullable()->after('capacite_totale');
            $table->string('code_postal', 20)->nullable()->after('adresse_siege');
            $table->string('boite_postale', 100)->nullable()->after('code_postal');
            $table->string('ville', 100)->nullable()->after('boite_postale');
            $table->string('quartier', 100)->nullable()->after('ville');
            $table->string('commune', 100)->nullable()->after('quartier');
            $table->string('departement', 100)->nullable()->after('commune');
            $table->string('region', 100)->nullable()->after('departement');
            $table->unsignedBigInteger('pays_id')->nullable()->after('region');

            // Contacts - Téléphones
            $table->string('telephone_principal', 20)->nullable()->after('pays_id');
            $table->string('telephone_2', 20)->nullable()->after('telephone_principal');
            $table->string('telephone_3', 20)->nullable()->after('telephone_2');

            // Contacts - WhatsApp
            $table->string('whatsapp_1', 20)->nullable()->after('telephone_3');
            $table->string('whatsapp_2', 20)->nullable()->after('whatsapp_1');

            // Contacts - Autres
            $table->string('fax', 20)->nullable()->after('whatsapp_2');
            $table->string('email_principal')->nullable()->after('fax');
            $table->string('email_1')->nullable()->after('email_principal');
            $table->string('site_web')->nullable()->after('email_1');
            $table->string('facebook')->nullable()->after('site_web');
            $table->string('linkedin')->nullable()->after('facebook');
            $table->string('twitter')->nullable()->after('linkedin');

            // Description
            $table->longText('description')->nullable()->after('twitter');
            $table->longText('vision')->nullable()->after('description');
            $table->longText('mission')->nullable()->after('vision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ecoles')) if (Schema::hasTable('ecoles')) Schema::table('ecoles', function (Blueprint $table) {
            $table->dropColumn([
                'adresse_siege', 'code_postal', 'boite_postale', 'ville', 'quartier',
                'commune', 'departement', 'region', 'pays_id',
                'telephone_principal', 'telephone_2', 'telephone_3',
                'whatsapp_1', 'whatsapp_2', 'fax',
                'email_principal', 'email_1', 'site_web', 'facebook', 'linkedin', 'twitter',
                'description', 'vision', 'mission'
            ]);
        });
    }
};
