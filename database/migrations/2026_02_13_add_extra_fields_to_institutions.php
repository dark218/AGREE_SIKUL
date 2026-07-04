<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (Schema::hasTable('institutions')) if (Schema::hasTable('institutions')) Schema::table('institutions', function (Blueprint $table) {
            // Address fields
            $table->string('code_postal', 20)->nullable()->after('adresse_siege');
            $table->string('boite_postale', 100)->nullable()->after('code_postal');
            $table->string('quartier', 100)->nullable()->after('boite_postale');
            $table->string('commune', 100)->nullable()->after('quartier');
            $table->string('ville', 100)->nullable()->after('commune');
            $table->string('departement', 100)->nullable()->after('ville');
            $table->string('region', 100)->nullable()->after('departement');

            // Ministry fields
            $table->string('ministere_tutelle_1', 255)->nullable()->after('numero_autorisation');
            $table->string('ministere_tutelle_2', 255)->nullable()->after('ministere_tutelle_1');
            $table->string('ministere_tutelle_3', 255)->nullable()->after('ministere_tutelle_2');
            $table->string('ministere_tutelle_4', 255)->nullable()->after('ministere_tutelle_3');

            // Additional contact fields
            $table->string('telephone_1', 20)->nullable()->after('telephone_principal');
            $table->string('telephone_2', 20)->nullable()->after('telephone_1');
            $table->string('telephone_3', 20)->nullable()->after('telephone_2');
            $table->string('whatsapp_1', 20)->nullable()->after('telephone_3');
            $table->string('whatsapp_2', 20)->nullable()->after('whatsapp_1');
            $table->string('fax', 20)->nullable()->after('whatsapp_2');
            $table->string('email_1', 255)->nullable()->after('email_principal');
            $table->string('email_2', 255)->nullable()->after('email_1');

            // Social media fields
            $table->string('facebook', 255)->nullable()->after('email_2');
            $table->string('linkedin', 255)->nullable()->after('facebook');
            $table->string('twitter', 255)->nullable()->after('linkedin');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('institutions')) if (Schema::hasTable('institutions')) Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn([
                'code_postal',
                'boite_postale',
                'quartier',
                'commune',
                'ville',
                'departement',
                'region',
                'ministere_tutelle_1',
                'ministere_tutelle_2',
                'ministere_tutelle_3',
                'ministere_tutelle_4',
                'telephone_1',
                'telephone_2',
                'telephone_3',
                'whatsapp_1',
                'whatsapp_2',
                'fax',
                'email_1',
                'email_2',
                'facebook',
                'linkedin',
                'twitter',
            ]);
        });
    }
};
