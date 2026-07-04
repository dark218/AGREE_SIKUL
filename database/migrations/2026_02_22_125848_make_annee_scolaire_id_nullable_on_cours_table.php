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
        if (Schema::hasTable('cours')) if (Schema::hasTable('cours')) Schema::table('cours', function (Blueprint $table) {
            $table->unsignedBigInteger('annee_scolaire_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cours')) if (Schema::hasTable('cours')) Schema::table('cours', function (Blueprint $table) {
            $table->unsignedBigInteger('annee_scolaire_id')->nullable(false)->change();
        });
    }
};
