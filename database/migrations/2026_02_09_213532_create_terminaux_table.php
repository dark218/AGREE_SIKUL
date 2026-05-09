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
        Schema::create('terminaux', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')
                ->unique()
                ->comment('Identifiant public unique du terminal');
            $table->enum('type_terminal', [
                'pos',
                'mobile',
                'kiosk',
                'web',
                'api'
            ])->comment('Type de terminal');
            $table->string('fabricant', 100)
                ->nullable()
                ->comment('Fabricant du terminal');

            $table->string('modele', 100)
                ->nullable()
                ->comment('Modèle du terminal');

            $table->string('numero_serie', 255)
                ->nullable()
                ->comment('Numéro de série matériel');
            $table->enum('statut', [
                'deploye',
                'actif',
                'suspendu',
                'retire'
            ])->default('deploye')
                ->comment('Statut opérationnel du terminal');

            $table->foreignId('points_vente_id')
                ->constrained('points_vente');
            $table->foreignId('marchand_id')
                ->nullable()
                ->constrained('marchands')
                ->comment('Marchand propriétaire du terminal');
            $table->string('version_firmware', 50)
                ->nullable()
                ->comment('Version du firmware du terminal');

            $table->string('pki_cert_id', 255)
                ->nullable()
                ->comment('Identifiant du certificat PKI');
            $table->timestamp('last_checkin')
                ->nullable()
                ->comment('Dernière communication du terminal avec le serveur');
            $table->json('metadata')
                ->nullable()
                ->comment('Métadonnées spécifiques au terminal');
            $table->string('motif',255)->nullable();
            $table->timestamp('validated_at')->nullable(); // Correction ici
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->foreignId('validated_by')
                ->nullable() // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('deleted_by')
                ->nullable() // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('suspended_by')
                ->nullable() // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('retired_by')
                ->nullable() // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');
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
        Schema::dropIfExists('terminaux');
    }
};
