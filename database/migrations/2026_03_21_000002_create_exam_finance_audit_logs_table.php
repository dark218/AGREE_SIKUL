<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exam_finance_audit_logs')) Schema::create('exam_finance_audit_logs', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('exam_poste_recette_id')
                ->constrained('exam_poste_recette')
                ->onDelete('cascade');

            $table->foreignId('auteur_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Action Log
            $table->enum('action', ['created', 'updated', 'status_changed', 'validated', 'rejected', 'closed', 'deleted'])
                ->index()
                ->comment('Type d\'action effectuée');

            // State Changes
            $table->string('ancienne_valeur_etat')
                ->nullable()
                ->comment('État précédent');

            $table->string('nouvelle_valeur_etat')
                ->nullable()
                ->comment('Nouvel état');

            // Amount Changes
            $table->decimal('montant_precedent', 15, 2)
                ->nullable()
                ->comment('Montant précédent');

            $table->decimal('montant_nouveau', 15, 2)
                ->nullable()
                ->comment('Nouveau montant');

            // Context
            $table->text('raison_changement')
                ->nullable()
                ->comment('Justification du changement');

            $table->longText('donnees_supplementaires')
                ->nullable()
                ->comment('Données JSON du changement');

            // Timestamps
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();

            // Indexes
            $table->index(['exam_poste_recette_id', 'action']);
            $table->index(['created_at']);
            $table->index(['auteur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_finance_audit_logs');
    }
};
