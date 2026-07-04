<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (Schema::hasTable('apprenants')) if (Schema::hasTable('apprenants')) Schema::table('apprenants', function (Blueprint $table) {
            // Make user_id nullable
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('apprenants')) if (Schema::hasTable('apprenants')) Schema::table('apprenants', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
