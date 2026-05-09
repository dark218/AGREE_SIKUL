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
        if (Schema::hasTable('transferts_stock_lignes')) if (Schema::hasTable('transferts_stock_lignes')) Schema::table('transferts_stock_lignes', function (Blueprint $table) {
            $table->integer('quantite_demandee')->default(0)->after('article_id');
            $table->integer('quantite_approuvee')->default(0)->after('quantite_demandee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transferts_stock_lignes')) if (Schema::hasTable('transferts_stock_lignes')) Schema::table('transferts_stock_lignes', function (Blueprint $table) {
            $table->dropColumn(['quantite_demandee', 'quantite_approuvee']);
        });
    }
};
