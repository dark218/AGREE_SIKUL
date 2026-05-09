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
        if (Schema::hasTable('moyennes_matieres')) if (Schema::hasTable('moyennes_matieres')) Schema::table('moyennes_matieres', function (Blueprint $table) {
            $table->enum('statut', ['actif', 'inactif', 'suspendu', 'archive'])->default('actif')->after('appreciation');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('moyennes_matieres')) if (Schema::hasTable('moyennes_matieres')) Schema::table('moyennes_matieres', function (Blueprint $table) {
            $table->dropIndex(['statut']);
            $table->dropColumn('statut');
        });
    }
};
