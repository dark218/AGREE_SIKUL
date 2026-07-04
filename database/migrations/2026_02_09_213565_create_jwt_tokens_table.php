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
        Schema::hasTable('jwt_tokens') ? null : Schema::create('jwt_tokens', function (Blueprint $table) {
            $table->id();
            
            /**
             * Token JWT unique (jti)
             */
            $table->string('token_id', 191)->unique()->comment('ID unique du token JWT (jti)');
            
            /**
             * Utilisateur propriétaire du token
             */
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Utilisateur propriétaire du token');
            
            /**
             * Informations sur l'appareil
             */
            $table->string('device_type', 100)->nullable()->comment('Type d\'appareil (mobile, web, desktop)');
            $table->string('device_name', 255)->nullable()->comment('Nom de l\'appareil');
            $table->string('platform', 100)->nullable()->comment('Plateforme (iOS, Android, Windows, etc.)');
            $table->string('user_agent', 500)->nullable()->comment('User agent du navigateur/application');
            
            /**
             * Informations de connexion
             */
            $table->string('ip_address', 45)->nullable()->comment('Adresse IP de la connexion');
            $table->timestamp('last_used_at')->nullable()->comment('Dernière utilisation du token');
            $table->timestamp('expires_at')->comment('Date d\'expiration du token');
            
            /**
             * Statut du token
             */
            $table->boolean('is_active')->default(true)->comment('Token actif ou non');
            
            /**
             * Métadonnées
             */
            $table->json('meta_json')->nullable()->comment('Métadonnées additionnelles');
            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->default('smilpay');
            $table->string('creation_hostname')->nullable();
            $table->string('modification_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            
            /**
             * Timestamps
             */
            $table->timestamps();
            
            /**
             * Index pour les performances
             */
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'expires_at']);
            $table->index(['expires_at']);
            $table->index(['is_active']);
            $table->index(['ip_address']);
            $table->index(['external_id']);
            $table->index(['source_system']);
            
            /**
             * Index unique pour éviter les doublons de token
             */
            $table->unique(['token_id', 'user_id'], 'unique_token_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jwt_tokens');
    }
};
