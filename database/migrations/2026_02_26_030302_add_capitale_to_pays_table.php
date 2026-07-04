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
        // idempotence guard
        if (Schema::hasTable('pays')) if (Schema::hasTable('pays')) Schema::table('pays', function (Blueprint $table) {
            $table->string('capitale')->nullable()->after('libelle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pays')) if (Schema::hasTable('pays')) Schema::table('pays', function (Blueprint $table) {
            $table->dropColumn('capitale');
        });
    }
};
