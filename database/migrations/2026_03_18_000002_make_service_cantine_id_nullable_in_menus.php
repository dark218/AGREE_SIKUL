<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menus')) if (Schema::hasTable('menus')) Schema::table('menus', function (Blueprint $table) {
            // Make service_cantine_id nullable since generic menus don't belong to a specific cantine
            $table->unsignedBigInteger('service_cantine_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) if (Schema::hasTable('menus')) Schema::table('menus', function (Blueprint $table) {
            $table->unsignedBigInteger('service_cantine_id')->nullable(false)->change();
        });
    }
};
