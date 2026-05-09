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
        if (!Schema::hasTable('users')) Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenoms')->nullable();
            $table->string('login')->unique();
            $table->string("full_login")->unique()->comment("Ex: +22501898..2323 permet de se connecter");
            $table->uuid('uuid')->unique()->comment("sera utiliser pour les apis et les vue on va éviter d'exposer l'id"); //sera utiliser pour les apis et les vue on va éviter d'exposer l'id
            $table->string('alias_smil', 100)->nullable()->unique()->comment("le pseudo / identifiant humain pour se faire payer"); //le pseudo / identifiant humain pour se faire payer
            $table->unsignedBigInteger('id_pass_paiement_prefere')->nullable()->comment("Moyen de paiement par defaut de l'user"); //Moyen de paiement par defaut de l'user
            $table->string('qr_data')->comment("Valeur du qr_code du user");
            $table->enum('kyc_status', ['non_verifie','en_attente','verifie','rejete'])->default('non_verifie')->comment("statut de la verification de l'utilisateur"); //statut de la verification de l'utilisateur
            $table->enum('statut',  ['non_actif','actif','suspendu','bloque'])->default('non_actif')->comment("statut de l'utilisateur , si le compte est utilisable ou pas dans le système"); //statut de l'utilisateur , si le compte est utilisable ou pas dans le système
            $table->json('metadata')->nullable();
            $table->string('numero_piece')->nullable();
            $table->dateTime("date_delivrance")->nullable();
            $table->dateTime("date_naissance")->nullable();
            $table->string("lieu_delivrance")->nullable();
            $table->string("lieu_naissance")->nullable();
            $table->enum('type_piece',["passport","cni","pc","ai"])->nullable();
            $table->enum('role',["superadmin","admin","marchand","caissier","manager","client","agent","service_client","service_validateur"])->nullable();
            $table->string('email')->nullable();
            $table->string('code_owner');
            $table->string('code_parrain')->nullable();
            $table->string('password')->nullable();
            $table->string('fcm_token','255')->nullable();
            $table->string('motif',255)->nullable();
            $table->timestamp('validated_at')->nullable(); // Correction ici
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
            $table->foreignId('blocked_by')
                ->nullable() // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');

            // Auth réseaux sociaux
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->string('provider_token')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
