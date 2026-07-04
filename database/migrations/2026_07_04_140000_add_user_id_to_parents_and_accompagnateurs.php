<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute `user_id` (FK vers `users`) sur `parents` et `accompagnateurs`.
 *
 * Justification : chaque parent / accompagnateur doit disposer d'un
 * compte utilisateur pour se connecter au portail (suivi des enfants,
 * notifications, signature électronique de justificatifs, etc.).
 *
 * Les Tuteurs ont déjà `user_id` depuis leur création initiale.
 *
 * Idempotent : ne re-fait rien si la colonne existe déjà.
 * onDelete('set null') pour préserver le profil si le user est supprimé.
 */
return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (Schema::hasTable('parents') && !Schema::hasColumn('parents', 'user_id')) {
            Schema::table('parents', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('accompagnateurs') && !Schema::hasColumn('accompagnateurs', 'user_id')) {
            Schema::table('accompagnateurs', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('parents') && Schema::hasColumn('parents', 'user_id')) {
            Schema::table('parents', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
        if (Schema::hasTable('accompagnateurs') && Schema::hasColumn('accompagnateurs', 'user_id')) {
            Schema::table('accompagnateurs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
