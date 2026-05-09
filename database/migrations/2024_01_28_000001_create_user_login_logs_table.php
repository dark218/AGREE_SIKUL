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
        if (Schema::hasTable('user_login_logs')) return;
        Schema::create('user_login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('device_id')->index(); // Pour identifier les appareils uniques
            $table->string('device_type'); // mobile, web, desktop
            $table->string('ip_address', 45)->index(); // IPv6 compatible
            $table->text('user_agent')->nullable(); // User agent du navigateur/app
            $table->timestamp('login_at')->index(); // Date/heure de connexion
            $table->timestamps();

            // Index composite pour les recherches rapides
            $table->index(['user_id', 'device_id']);
            $table->index(['user_id', 'login_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_login_logs');
    }
};
