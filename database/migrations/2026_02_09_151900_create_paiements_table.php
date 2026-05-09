<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('frais_id');
            $table->unsignedBigInteger('apprenant_id');
            $table->unsignedBigInteger('montant_cents')->default(0);
            $table->enum('mode_paiement', ['espece', 'cheque', 'virement', 'mobile_money', 'carte'])->default('espece');
            $table->string('reference')->nullable()->unique();
            $table->date('date_paiement');
            $table->unsignedBigInteger('recu_par')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->nullable();
            $table->string('creation_hostname')->nullable();
            $table->string('modification_hostname')->nullable();
            $table->string('deletion_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();

            $table->index(['external_id']);
            $table->index(['source_system']);

            $table->foreign('frais_id')->references('id')->on('frais')->cascadeOnDelete();
            $table->foreign('apprenant_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('recu_par')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
