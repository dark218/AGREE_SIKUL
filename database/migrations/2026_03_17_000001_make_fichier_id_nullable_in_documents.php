<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (Schema::hasTable('documents')) if (Schema::hasTable('documents')) Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('fichier_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('documents')) if (Schema::hasTable('documents')) Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('fichier_id')->change();
        });
    }
};
