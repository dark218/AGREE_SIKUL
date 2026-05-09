<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('devises_pays')) Schema::create('devises_pays', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->foreignId('pays_id')->constrained('pays')->onDelete('cascade');
            $table->foreignId('devise_id')->constrained('devises')->onDelete('cascade');
            $table->decimal('taux_change', 10, 4)->default(1);
            $table->enum('etat', ['actif', 'inactif'])->default('actif')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['pays_id', 'devise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devises_pays');
    }
};
