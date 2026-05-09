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
        DB::statement("
            ALTER TABLE transferts_stock
            MODIFY statut ENUM(
                'en_cours',
                'partiel',
                'confirme',
                'annule',
                'receptionne'
            ) NOT NULL DEFAULT 'en_cours'
        ");
        if (Schema::hasTable('transferts_stock')) if (Schema::hasTable('transferts_stock')) Schema::table('transferts_stock', function (Blueprint $table) {
            $table->dateTime('date_reception')
                ->nullable()
                ->after('date_confirmation')
                ->comment('Date de réception effective du transfert');

            $table->foreignId('recu_par')
                ->nullable()
                ->after('date_reception')
                ->constrained('employes')
                ->nullOnDelete()
                ->comment('Employé ayant réceptionné le transfert');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transferts_stock')) if (Schema::hasTable('transferts_stock')) Schema::table('transferts_stock', function (Blueprint $table) {
            $table->dropForeign(['recu_par']);
            $table->dropColumn(['date_reception', 'recu_par']);
        });
        DB::statement("
            ALTER TABLE transferts_stock
            MODIFY statut ENUM(
                'en_cours',
                'partiel',
                'confirme',
                'annule'
            ) NOT NULL DEFAULT 'en_cours'
        ");
    }
};
