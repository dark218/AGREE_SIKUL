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
        // idempotence guard
        if (Schema::hasTable('ouvrages')) if (Schema::hasTable('ouvrages')) Schema::table('ouvrages', function (Blueprint $table) {
            $table->string('statut')->default('actif')->after('nombre_exemplaires');
            $table->text('description')->nullable()->after('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ouvrages')) if (Schema::hasTable('ouvrages')) Schema::table('ouvrages', function (Blueprint $table) {
            $table->dropColumn(['statut', 'description']);
        });
    }
};
