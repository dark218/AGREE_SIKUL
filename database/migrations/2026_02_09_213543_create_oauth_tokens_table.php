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
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_users')->constrained('users')->onDelete('cascade');
            $table->enum('client_id', [
                'mobile_android',
                'mobile_ios',
                'web_admin',
                'pos_terminal',
                'partner_api',
            ])->comment("Type de client/app ayant généré le token (mobile, web, POS, partenaire)");

            $table->string('jeton', 512)->comment("Jeton d'accès (token) utilisé pour l'authentification aux APIs");
            $table->boolean('revoke')->default(false)->comment("Indique si le token a été révoqué (true = invalide)");
            $table->timestamp('expire_le')->nullable()->comment("Date/heure d'expiration du token OAuth");
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
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
    }
};
