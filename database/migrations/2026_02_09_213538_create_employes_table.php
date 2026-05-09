<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('points_vente_id')->nullable()->constrained('points_vente')->onUpdate('cascade');
            $table->foreignId('users_id')->constrained('users')->onDelete('cascade');
            $table->string('code_employe')->nullable()->unique();
            $table->date('date_embauche')->nullable();
            $table->enum('type_employe', ['caissier', 'manager'])->nullable();
            $table->json('shift_info')->nullable();
            $table
                ->foreignId('create_by')
                ->nullable()  // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');
            $table->timestamp('validated_at')->nullable();  // Correction ici
            $table
                ->foreignId('validated_by')
                ->nullable()  // Rendre la clé étrangère nullable si besoin
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
            $table->unique(['users_id'], 'uniq_marchand_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employes');
    }
};
