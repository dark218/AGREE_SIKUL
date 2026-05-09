<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only run migrations if the reservations table exists and hasn't been migrated yet
        if (!Schema::hasTable('reservations')) {
            return;
        }

        // Rename columns if they still exist with old names
        if (Schema::hasColumn('reservations', 'ouvrage_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->renameColumn('ouvrage_id', 'exemplaire_id');
            });
        }

        if (Schema::hasColumn('reservations', 'user_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->renameColumn('user_id', 'utilisateur_id');
            });
        }

        // Add new columns if they don't exist
        if (!Schema::hasColumn('reservations', 'date_expiration')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->date('date_expiration')->after('date_reservation');
            });
        }

        if (!Schema::hasColumn('reservations', 'date_notification')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->date('date_notification')->nullable()->after('date_expiration');
            });
        }

        if (!Schema::hasColumn('reservations', 'priorite')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->enum('priorite', ['basse', 'normale', 'haute'])->default('normale')->after('date_notification');
            });
        }
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['date_expiration', 'date_notification', 'priorite']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            // Revert statut enum
            $table->enum('statut', ['en_attente', 'satisfaite', 'annulea'])->default('en_attente')->change();
        });

        Schema::table('reservations', function (Blueprint $table) {
            // Drop new foreign keys
            $table->dropForeign(['exemplaire_id']);
            $table->dropForeign(['utilisateur_id']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            // Rename columns back
            $table->renameColumn('exemplaire_id', 'ouvrage_id');
            $table->renameColumn('utilisateur_id', 'user_id');
        });

        Schema::table('reservations', function (Blueprint $table) {
            // Add back old foreign keys
            $table->foreign('ouvrage_id')->references('id')->on('ouvrages')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
