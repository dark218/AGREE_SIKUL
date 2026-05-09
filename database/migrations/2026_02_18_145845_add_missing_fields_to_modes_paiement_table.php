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
        if (Schema::hasTable('modes_paiement')) if (Schema::hasTable('modes_paiement')) Schema::table('modes_paiement', function (Blueprint $table) {
            $table->string('type_mode')->nullable()->after('libelle');
            $table->boolean('necessite_reference')->default(false)->after('type_mode');
            $table->integer('delai_compensation_jours')->nullable()->after('necessite_reference');
            $table->decimal('frais_pourcentage', 8, 2)->nullable()->after('delai_compensation_jours');
            $table->decimal('frais_fixe', 10, 2)->nullable()->after('frais_pourcentage');
            $table->decimal('montant_min', 10, 2)->nullable()->after('frais_fixe');
            $table->decimal('montant_max', 10, 2)->nullable()->after('montant_min');
            $table->unsignedBigInteger('fournisseur_paiement_id')->nullable()->after('montant_max');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('modes_paiement')) if (Schema::hasTable('modes_paiement')) Schema::table('modes_paiement', function (Blueprint $table) {
            $table->dropColumn([
                'type_mode',
                'necessite_reference',
                'delai_compensation_jours',
                'frais_pourcentage',
                'frais_fixe',
                'montant_min',
                'montant_max',
                'fournisseur_paiement_id',
            ]);
        });
    }
};
