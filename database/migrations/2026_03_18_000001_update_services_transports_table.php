<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        Schema::table('services_transports', function (Blueprint $table) {
            // Drop old columns only if they exist
            if (Schema::hasColumn('services_transports', 'nom')) {
                $table->dropColumn('nom');
            }
            if (Schema::hasColumn('services_transports', 'itineraire')) {
                $table->dropColumn('itineraire');
            }
            if (Schema::hasColumn('services_transports', 'capacite')) {
                $table->dropColumn('capacite');
            }
            // Skip responsable_id as it may have already been deleted
            if (Schema::hasColumn('services_transports', 'prix_mensuel_cents')) {
                $table->dropColumn('prix_mensuel_cents');
            }

            // Add new columns
            if (!Schema::hasColumn('services_transports', 'annee_scolaire_id')) {
                $table->unsignedBigInteger('annee_scolaire_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('services_transports', 'campus_id')) {
                $table->unsignedBigInteger('campus_id')->nullable()->after('ecole_id');
            }
            if (!Schema::hasColumn('services_transports', 'zone')) {
                $table->string('zone')->nullable()->after('campus_id');
            }
            if (!Schema::hasColumn('services_transports', 'ligne')) {
                $table->string('ligne')->nullable()->after('zone');
            }
            if (!Schema::hasColumn('services_transports', 'point_depart')) {
                $table->string('point_depart')->nullable()->after('ligne');
            }

            // Add 10 waypoints
            for ($i = 1; $i <= 10; $i++) {
                if (!Schema::hasColumn('services_transports', "point_$i")) {
                    $column = ($i === 1) ? 'point_depart' : "point_" . ($i - 1);
                    $table->string("point_$i")->nullable()->after($column);
                }
            }

            if (!Schema::hasColumn('services_transports', 'tarif_mensuel')) {
                $table->decimal('tarif_mensuel', 12, 2)->default(0)->after('point_10');
            }
            if (!Schema::hasColumn('services_transports', 'tarif_trimestriel')) {
                $table->decimal('tarif_trimestriel', 12, 2)->default(0)->after('tarif_mensuel');
            }
            if (!Schema::hasColumn('services_transports', 'tarif_semestriel')) {
                $table->decimal('tarif_semestriel', 12, 2)->default(0)->after('tarif_trimestriel');
            }
            if (!Schema::hasColumn('services_transports', 'tarif_annuel')) {
                $table->decimal('tarif_annuel', 12, 2)->default(0)->after('tarif_semestriel');
            }
            if (!Schema::hasColumn('services_transports', 'date_debut')) {
                $table->date('date_debut')->nullable()->after('tarif_annuel');
            }
            if (!Schema::hasColumn('services_transports', 'date_fin')) {
                $table->date('date_fin')->nullable()->after('date_debut');
            }
            if (!Schema::hasColumn('services_transports', 'etat')) {
                $table->enum('etat', ['actif', 'inactif'])->default('actif')->after('date_fin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services_transports', function (Blueprint $table) {
            // Drop new columns
            if (Schema::hasColumn('services_transports', 'annee_scolaire_id')) {
                $table->dropColumn('annee_scolaire_id');
            }
            if (Schema::hasColumn('services_transports', 'campus_id')) {
                $table->dropColumn('campus_id');
            }
            if (Schema::hasColumn('services_transports', 'zone')) {
                $table->dropColumn('zone');
            }
            if (Schema::hasColumn('services_transports', 'ligne')) {
                $table->dropColumn('ligne');
            }
            if (Schema::hasColumn('services_transports', 'point_depart')) {
                $table->dropColumn('point_depart');
            }
            for ($i = 1; $i <= 10; $i++) {
                if (Schema::hasColumn('services_transports', "point_$i")) {
                    $table->dropColumn("point_$i");
                }
            }
            if (Schema::hasColumn('services_transports', 'tarif_mensuel')) {
                $table->dropColumn('tarif_mensuel');
            }
            if (Schema::hasColumn('services_transports', 'tarif_trimestriel')) {
                $table->dropColumn('tarif_trimestriel');
            }
            if (Schema::hasColumn('services_transports', 'tarif_semestriel')) {
                $table->dropColumn('tarif_semestriel');
            }
            if (Schema::hasColumn('services_transports', 'tarif_annuel')) {
                $table->dropColumn('tarif_annuel');
            }
            if (Schema::hasColumn('services_transports', 'date_debut')) {
                $table->dropColumn('date_debut');
            }
            if (Schema::hasColumn('services_transports', 'date_fin')) {
                $table->dropColumn('date_fin');
            }
            if (Schema::hasColumn('services_transports', 'etat')) {
                $table->dropColumn('etat');
            }

            // Restore old columns
            $table->string('nom');
            $table->string('itineraire')->nullable();
            $table->integer('capacite')->default(0);
            $table->unsignedBigInteger('responsable_id')->nullable();
            $table->unsignedBigInteger('prix_mensuel_cents')->default(0);
        });
    }
};
