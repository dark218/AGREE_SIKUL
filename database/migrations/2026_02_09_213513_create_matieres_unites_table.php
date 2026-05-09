<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matieres_unites', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->string('libelle');
            $table->foreignId('niveau_id')->constrained('niveaux_etudes')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('cycle_id')->constrained('cycles_enseignement')->onDelete('cascade');
            $table->decimal('note_max', 5, 2)->default(20);
            $table->decimal('coefficient', 5, 2)->default(1);
            $table->foreignId('pays_id')->constrained('pays')->onDelete('cascade');
            $table->enum('etat', ['actif', 'inactif'])->default('actif')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['niveau_id', 'section_id', 'etat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matieres_unites');
    }
};
