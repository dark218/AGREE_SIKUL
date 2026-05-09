<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annees_scolaires', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique()->index();
            $table->string('libelle');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->integer('duree')->comment('Durée en jours');
            $table->boolean('est_courante')->default(false)->index();
            $table->foreignId('pays_id')->nullable()->constrained('pays')->onDelete('cascade');
            $table->enum('statut', ['planifiee', 'en_cours', 'terminee'])->default('planifiee')->nullable();
            $table->enum('etat', ['actif', 'inactif'])->default('actif')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['pays_id', 'est_courante', 'etat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annees_scolaires');
    }
};
