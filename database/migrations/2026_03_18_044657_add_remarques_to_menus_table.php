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
        if (Schema::hasTable('menus')) if (Schema::hasTable('menus')) Schema::table('menus', function (Blueprint $table) {
            $table->text('remarques')->nullable()->after('dessert');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('menus')) if (Schema::hasTable('menus')) Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('remarques');
        });
    }
};
