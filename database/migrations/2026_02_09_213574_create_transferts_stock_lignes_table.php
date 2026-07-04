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
        Schema::hasTable('transferts_stock_lignes') ? null : Schema::create('transferts_stock_lignes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('transfert_stock_id')
                ->constrained('transferts_stock')
                ->cascadeOnDelete()
                ->comment('Bon de transfert parent');

            $table->foreignId('article_id')
                ->constrained('articles')
                ->comment('Article concerné');

            $table->integer('stock_source_avant')
                ->nullable()
                ->comment('Stock source avant transfert');

            $table->integer('stock_source_apres')
                ->nullable()
                ->comment('Stock source après transfert');

            $table->integer('stock_destination_avant')
                ->nullable()
                ->comment('Stock destination avant transfert');

            $table->integer('stock_destination_apres')
                ->nullable()
                ->comment('Stock destination après transfert');
            $table->enum('statut', [
                'en_attente',
                'partiel',
                'transfere',
                'annule'
            ])->default('en_attente')
                ->comment('Statut de la ligne de transfert');

            $table->text('commentaire')
                ->nullable()
                ->comment('Motif ou remarque (ex: stock insuffisant)');

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
            $table->softDeletes();

            $table->index(['transfert_stock_id']);
            $table->index(['article_id']);
            $table->index(['statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferts_stock_lignes');
    }
};
