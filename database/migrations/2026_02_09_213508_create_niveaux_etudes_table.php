<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('niveaux_etudes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->string('libelle');
            $table->foreignId('cycle_id')->constrained('cycles_enseignement')->onDelete('cascade');
            $table->foreignId('pays_id')->constrained('pays')->onDelete('cascade');
            $table->enum('etat', ['actif', 'inactif'])->default('actif')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['cycle_id', 'pays_id', 'etat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('niveaux_etudes');
    }
};
