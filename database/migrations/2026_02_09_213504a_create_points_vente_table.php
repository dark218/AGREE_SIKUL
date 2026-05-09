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
        Schema::create('points_vente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marchand_id')->constrained('marchands')->onDelete('cascade');
            $table->string('nom');
            $table->text('adresse')->nullable();
            $table->string('telephone')->nullable();
            $table->double('longitude',10,8)->nullable(); // lat,long ou GeoJSON simplifié
            $table->double('latitude',10,8)->nullable(); // lat,long ou GeoJSON simplifié
            $table->json('param_pos_json')->nullable();
            $table->foreignId('photo_id')->nullable()->constrained('fichier')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('cascade');
            $table->foreignId('create_by')->nullable() // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');
            $table->enum('statut',  ['non_actif','actif','suspendu','bloque'])->default('non_actif')->comment("statut de l'utilisateur , si le compte est utilisable ou pas dans le système"); //statut de l'utilisateur , si le compte est utilisable ou pas dans le système
            $table->string('motif',255)->nullable();
            $table->timestamp('validated_at')->nullable(); // Correction ici
            $table->foreignId('validated_by')
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
            $table->index(['external_id']);
            $table->index(['source_system']);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['marchand_id', 'nom']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_vente');
    }
};
