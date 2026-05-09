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
        if (Schema::hasTable('points_vente')) if (Schema::hasTable('points_vente')) Schema::table('points_vente', function (Blueprint $table) {
            $table->foreignId('parent_points_vente_id')
                ->nullable()
                ->constrained('points_vente')
                ->comment("Emplacement parent (ex: boutique pour une caisse)");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('points_vente')) if (Schema::hasTable('points_vente')) Schema::table('points_vente', function (Blueprint $table) {
            $table->dropForeign(['parent_points_vente_id']);
            $table->dropIndex(['parent_points_vente_id']);
            $table->dropColumn('parent_points_vente_id');
        });
    }
};
