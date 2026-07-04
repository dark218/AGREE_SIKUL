<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (Schema::hasTable('apprenants') && !Schema::hasColumn('apprenants', 'photo')) {
            Schema::table('apprenants', function (Blueprint $table) {
                $table->string('photo', 500)->nullable()->after('groupe_sanguin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('apprenants') && Schema::hasColumn('apprenants', 'photo')) {
            Schema::table('apprenants', function (Blueprint $table) {
                $table->dropColumn('photo');
            });
        }
    }
};
