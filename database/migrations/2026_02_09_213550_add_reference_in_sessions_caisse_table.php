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
        if (Schema::hasTable('sessions_caisse')) if (Schema::hasTable('sessions_caisse')) Schema::table('sessions_caisse', function (Blueprint $table) {
            $table->string('reference',255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sessions_caisse')) if (Schema::hasTable('sessions_caisse')) Schema::table('sessions_caisse', function (Blueprint $table) {
            $table->dropColumn('reference');
        });
    }
};
