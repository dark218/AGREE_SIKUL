<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dossiers_apprenants')) if (Schema::hasTable('dossiers_apprenants')) Schema::table('dossiers_apprenants', function (Blueprint $table) {
            $table->string('extrait_naissance')->nullable()->after('apprenant_id');
            $table->string('certificat_residence')->nullable()->after('extrait_naissance');
            $table->string('carnet_sante')->nullable()->after('certificat_residence');
            $table->string('dernier_bulletin')->nullable()->after('carnet_sante');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('dossiers_apprenants')) if (Schema::hasTable('dossiers_apprenants')) Schema::table('dossiers_apprenants', function (Blueprint $table) {
            $table->dropColumn(['extrait_naissance', 'certificat_residence', 'carnet_sante', 'dernier_bulletin']);
        });
    }
};
