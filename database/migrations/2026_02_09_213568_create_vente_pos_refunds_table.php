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
        // idempotence guard
        Schema::hasTable('vente_pos_refunds') ? null : Schema::create('vente_pos_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_pos_id')->constrained('ventes_pos');
            $table->enum('mode_paiement', ['espece', 'electronique']);
            $table->unsignedBigInteger('montant_cents');
            $table->string('motif', 255);
            $table->foreignId('refunded_by')->constrained('users');
            $table->timestamp('refunded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vente_pos_refunds');
    }
};
