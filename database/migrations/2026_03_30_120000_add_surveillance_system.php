<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table des logs de surveillance (anti-triche)
        if (!Schema::hasTable('logs_surveillance_examen')) Schema::create('logs_surveillance_examen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tentative_examen_id')->constrained('tentatives_examen')->onDelete('cascade');
            $table->foreignId('apprenant_id')->constrained('apprenants')->onDelete('cascade');
            $table->string('type_incident');
            // Types: tab_switch, copy_attempt, paste_attempt, right_click, fullscreen_exit,
            //        screen_capture, devtools_open, window_blur, window_resize
            $table->text('details')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('horodatage');
            $table->timestamps();
            $table->index(['tentative_examen_id', 'type_incident']);
        });

        // Ajouter colonnes de surveillance à tentatives_examen
        if (Schema::hasTable('tentatives_examen') && !Schema::hasColumn('tentatives_examen', 'nb_changements_onglet')) {
            Schema::table('tentatives_examen', function (Blueprint $table) {
                $table->integer('nb_changements_onglet')->default(0)->after('ip_address');
                $table->integer('nb_tentatives_copie')->default(0)->after('nb_changements_onglet');
                $table->integer('nb_sorties_fullscreen')->default(0)->after('nb_tentatives_copie');
                $table->integer('nb_incidents_total')->default(0)->after('nb_sorties_fullscreen');
                $table->boolean('suspicion_triche')->default(false)->after('nb_incidents_total');
                $table->string('navigateur')->nullable()->after('suspicion_triche');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_surveillance_examen');
        Schema::table('tentatives_examen', function (Blueprint $table) {
            $table->dropColumn([
                'nb_changements_onglet', 'nb_tentatives_copie',
                'nb_sorties_fullscreen', 'nb_incidents_total',
                'suspicion_triche', 'navigateur'
            ]);
        });
    }
};
