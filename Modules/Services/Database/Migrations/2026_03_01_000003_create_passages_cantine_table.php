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
        if (!Schema::hasTable('passages_cantine')) Schema::create('passages_cantine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_cantine_id')->constrained('inscriptions_cantine')->cascadeOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained('menus')->onDelete('set null');
            $table->dateTime('date_passage');
            $table->string('statut')->nullable(); // present, absent, justifie
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['inscription_cantine_id', 'date_passage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passages_cantine');
    }
};
