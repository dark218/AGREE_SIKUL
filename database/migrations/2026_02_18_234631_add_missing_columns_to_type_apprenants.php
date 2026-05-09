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
        Schema::table('type_apprenants', function (Blueprint $table) {
            if (!Schema::hasColumn('type_apprenants', 'poids')) {
                $table->integer('poids')->nullable();
            }
            if (!Schema::hasColumn('type_apprenants', 'age_min')) {
                $table->integer('age_min')->nullable();
            }
            if (!Schema::hasColumn('type_apprenants', 'age_max')) {
                $table->integer('age_max')->nullable();
            }
            if (!Schema::hasColumn('type_apprenants', 'necessite_tuteur')) {
                $table->boolean('necessite_tuteur')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('type_apprenants', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('type_apprenants', 'poids')) {
                $columns[] = 'poids';
            }
            if (Schema::hasColumn('type_apprenants', 'age_min')) {
                $columns[] = 'age_min';
            }
            if (Schema::hasColumn('type_apprenants', 'age_max')) {
                $columns[] = 'age_max';
            }
            if (Schema::hasColumn('type_apprenants', 'necessite_tuteur')) {
                $columns[] = 'necessite_tuteur';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
