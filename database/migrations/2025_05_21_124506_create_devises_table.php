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
        if (Schema::hasTable('devises')) return;
        Schema::create('devises', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->string('code_iso', 3)->unique()->nullable();
            $table->string('symbol', 10)->nullable();
            $table->integer('decimal_places')->default(2);
            $table->boolean('is_default')->default(false);
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);
            $table->string('libelle');
            $table->foreignId('pays_id')->nullable()->constrained('pays')->onDelete('set null');
            $table->enum('etat', ['actif', 'inactif'])->default('actif');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['code_iso', 'is_default']);
            $table->index(['pays_id', 'etat']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devises');
    }
};
