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
        if (Schema::hasTable('users')) if (Schema::hasTable('users')) Schema::table('users', function (Blueprint $table) {
            $table->foreignId('users_creation_id')->nullable()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) if (Schema::hasTable('users')) Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId("users_creation_id");
        });
    }
};
