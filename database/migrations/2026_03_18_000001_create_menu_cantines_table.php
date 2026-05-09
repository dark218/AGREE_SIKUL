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
        if (!Schema::hasTable('menu_cantines')) Schema::create('menu_cantines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_cantine_id')->nullable();
            $table->date('week_start_date')->nullable();
            $table->date('week_end_date')->nullable();
            $table->string('week_name')->nullable();
            $table->integer('week_number')->nullable();
            $table->integer('year')->nullable();
            $table->enum('jour', ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'])->nullable();
            $table->text('entree')->nullable();
            $table->text('plat')->nullable();
            $table->text('dessert')->nullable();
            $table->text('remarques')->nullable();
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['service_cantine_id', 'week_start_date']);
            $table->index(['week_start_date', 'jour']);
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_cantines');
    }
};
