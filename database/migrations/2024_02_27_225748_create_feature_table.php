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
        if (Schema::hasTable('feature')) return;
        Schema::create('feature', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 125)->nullable();
            $table->string('libelle_en', 125)->nullable();
            $table->foreignId('module_id')->constrained('module');
            $table->string('menu_url', 125)->nullable();
            $table->string('icone', 2500)->nullable();
            $table->integer('ordre')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature');
    }
};
