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
        if (!Schema::hasTable('sessions')) Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45)
                ->nullable()
                ->comment("Adresse IP de l'utilisateur (IPv4 ou IPv6)");

            $table->text('user_agent')
                ->nullable()
                ->comment("Informations sur le navigateur ou l'appareil utilisé (Chrome, Safari, iPhone, etc.)");

            $table->longText('payload')
                ->comment("Contenu sérialisé de la session (données PHP, CSRF, messages flash, etc.)");

            $table->integer('last_activity')
                ->comment("Timestamp UNIX indiquant la dernière activité de la session (sert au timeout & nettoyage)");
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


            $table->index('user_id');
            $table->index('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
