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
        if (Schema::hasTable('errorlogs')) return;
        Schema::create('errorlogs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("module");
            $table->string("methode");
            $table->text("message");
            $table->boolean("state")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('errorlogs');
    }
};
