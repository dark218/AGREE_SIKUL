<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_apprenants', function (Blueprint $table) {
            if (Schema::hasColumn('type_apprenants', 'poids')) {
                $table->dropColumn('poids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('type_apprenants', function (Blueprint $table) {
            $table->decimal('poids', 5, 2)->default(1)->after('cycle_id');
        });
    }
};
