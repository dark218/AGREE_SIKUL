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
        if (Schema::hasTable('sessions')) if (Schema::hasTable('sessions')) Schema::table('sessions', function (Blueprint $table) {
            $table->text('fcm_token')->nullable()->after('user_agent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sessions')) if (Schema::hasTable('sessions')) Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
    }
};
