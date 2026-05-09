<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services_cantines', function (Blueprint $table) {
            if (Schema::hasColumn('services_cantines', 'capacite')) {
                $table->integer('capacite')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('services_cantines', function (Blueprint $table) {
            if (Schema::hasColumn('services_cantines', 'capacite')) {
                $table->integer('capacite')->nullable(false)->change();
            }
        });
    }
};
