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
        if (Schema::hasTable('categories_documents')) if (Schema::hasTable('categories_documents')) Schema::table('categories_documents', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
            $table->string('couleur')->nullable()->after('icone');
            $table->enum('statut', ['actif', 'inactif'])->default('actif')->after('couleur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('categories_documents')) if (Schema::hasTable('categories_documents')) Schema::table('categories_documents', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'couleur', 'statut']);
        });
    }
};
