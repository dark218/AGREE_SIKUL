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
            $table->unsignedBigInteger("pays_id")->nullable();
            $table->foreign("pays_id")->references("id")->on("pays")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) if (Schema::hasTable('users')) Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId("pays_id");
        });
    }
};
