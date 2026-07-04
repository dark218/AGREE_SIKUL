<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        Schema::table('categorie_apprenants', function (Blueprint $table) {
            if (Schema::hasColumn('categorie_apprenants', 'necessite_justificatif')) {
                $table->dropColumn('necessite_justificatif');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categorie_apprenants', function (Blueprint $table) {
            if (!Schema::hasColumn('categorie_apprenants', 'necessite_justificatif')) {
                $table->boolean('necessite_justificatif')->default(false);
            }
        });
    }
};
