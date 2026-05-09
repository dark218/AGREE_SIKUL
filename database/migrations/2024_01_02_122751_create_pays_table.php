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
        if (Schema::hasTable('pays')) return;
        Schema::create('pays', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->string('code_3_chars')->nullable()->index();
            $table->string('code_2_chars')->nullable();
            $table->string('libelle');
            $table->integer('nombre')->nullable();
            $table->string('continent')->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['etat']);
            $table->index(['code_3_chars', 'code_2_chars']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pays');
    }
};
