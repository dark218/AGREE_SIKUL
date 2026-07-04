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
        Schema::table('apprenants', function (Blueprint $table) {
            // Add missing name fields if they don't exist
            if (!Schema::hasColumn('apprenants', 'nom')) {
                $table->string('nom')->nullable()->after('matricule');
            }
            if (!Schema::hasColumn('apprenants', 'prenoms')) {
                $table->string('prenoms')->nullable()->after('nom');
            }

            // Add missing contact fields
            if (!Schema::hasColumn('apprenants', 'email')) {
                $table->string('email')->nullable()->after('sexe');
            }
            if (!Schema::hasColumn('apprenants', 'telephone')) {
                $table->string('telephone', 20)->nullable()->after('email');
            }
            if (!Schema::hasColumn('apprenants', 'adresse')) {
                $table->text('adresse')->nullable()->after('telephone');
            }

            // Add missing academic fields
            if (!Schema::hasColumn('apprenants', 'classe_id')) {
                $table->unsignedBigInteger('classe_id')->nullable()->after('groupe_sanguin');
                $table->foreign('classe_id')->references('id')->on('classes')->onDelete('set null');
            }
            if (!Schema::hasColumn('apprenants', 'numero_inscription')) {
                $table->string('numero_inscription', 100)->nullable()->after('classe_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback not needed
    }
};
