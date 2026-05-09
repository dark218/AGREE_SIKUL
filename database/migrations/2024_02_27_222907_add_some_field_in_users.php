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
            $table->unsignedBigInteger("photoprofile_id")->nullable();
            $table->foreign("photoprofile_id")->references("id")->on("fichier");
            $table->unsignedBigInteger("piecerecto_id")->nullable();
            $table->foreign("piecerecto_id")->references("id")->on("fichier");
            $table->unsignedBigInteger("pieceverso_id")->nullable();
            $table->foreign("pieceverso_id")->references("id")->on("fichier");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) if (Schema::hasTable('users')) Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId("photoprofile_id");
            $table->dropConstrainedForeignId("piecerecto_id");
            $table->dropConstrainedForeignId("pieceverso_id");
        });
    }
};
