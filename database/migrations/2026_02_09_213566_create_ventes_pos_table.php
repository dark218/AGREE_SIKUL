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
        Schema::create('ventes_pos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('points_vente_id')
                ->constrained('points_vente');

            $table->foreignId('sessions_caisse_id')
                ->nullable()
                ->constrained('sessions_caisse');

            $table->foreignId('employe_id')
                ->nullable()
                ->constrained('employes');

            $table->string('devise', 3)->comment('Code devise ISO 4217 ex: XOF, USD, EUR');

            $table->unsignedBigInteger('total_cents');
            $table->unsignedBigInteger('refund_cents');

            $table->unsignedBigInteger('montant_espece_cents')->default(0);
            $table->unsignedBigInteger('montant_non_espece_cents')->default(0);

            $table->enum('mode_paiement', ['espece', 'electronique', 'mixte'])
                ->default('espece');

            $table->enum('statut', [
                'en_attente',
                'validee',
                'annulee',
                'remboursee',
                'partielle'
            ])->default('en_attente');

            $table->string('numero_ticket', 50)->nullable()->unique();
            $table->string('reference', 255)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refund_at')->nullable();
            $table->foreignId('deleted_by')
                ->nullable() // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('cancelled_by')
                ->nullable() // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('refund_by')
                ->nullable() // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('cancel_motif', 255)->nullable();
            $table->string('refund_motif', 255)->nullable();
            $table->unsignedBigInteger('total_rembourse_cents')->default(0);
            $table->unsignedBigInteger('rembourse_espece_cents')->default(0);
            $table->unsignedBigInteger('rembourse_non_espece_cents')->default(0);
            $table->unsignedBigInteger('rembourse_libre_cents')->default(0)
                ->comment('Montant remboursé hors lignes (geste, ajustement)');
            $table->string('motif_remboursement', 255)->nullable();
            $table->json('meta_json')->nullable();
            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->default('smilpay');
            $table->string('creation_hostname')->nullable();
            $table->string('modification_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();
            $table->index(['external_id']);
            $table->index(['source_system']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventes_pos');
    }
};
