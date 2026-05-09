<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('cours')) if (Schema::hasTable('cours')) Schema::table('cours', function (Blueprint $table) {
            // Change date_debut and date_fin from date to datetime
            $table->dateTime('date_debut')->nullable()->change();
            $table->dateTime('date_fin')->nullable()->change();
        });
    }

    public function down()
    {
        if (Schema::hasTable('cours')) if (Schema::hasTable('cours')) Schema::table('cours', function (Blueprint $table) {
            $table->date('date_debut')->nullable()->change();
            $table->date('date_fin')->nullable()->change();
        });
    }
};
