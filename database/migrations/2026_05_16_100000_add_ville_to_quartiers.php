<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quartiers')) {
            return;
        }
        Schema::table('quartiers', function (Blueprint $table) {
            if (!Schema::hasColumn('quartiers', 'ville')) {
                $table->string('ville', 100)->nullable()->after('libelle');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('quartiers') && Schema::hasColumn('quartiers', 'ville')) {
            Schema::table('quartiers', function (Blueprint $table) {
                $table->dropColumn('ville');
            });
        }
    }
};
