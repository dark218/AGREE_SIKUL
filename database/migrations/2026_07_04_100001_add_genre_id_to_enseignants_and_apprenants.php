<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute `genre_id` (FK vers `genres`) sur `apprenants` et `enseignants`,
 * puis rattache automatiquement les enregistrements existants au bon genre :
 *   • apprenants.sexe = 'M'  → genres.code = 'M'
 *   • apprenants.sexe = 'F'  → genres.code = 'F'
 *   • enseignants.gender = 'M' → 'M'
 *   • enseignants.gender = 'F' → 'F'
 *   • enseignants.gender = 'Autre' → 'AUTRE'
 *
 * Les anciennes colonnes `sexe` / `gender` restent (nullable) en dépréciation.
 * Une migration ultérieure pourra les drop quand tout le code aura basculé.
 */
return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        // Apprenants
        if (Schema::hasTable('apprenants') && !Schema::hasColumn('apprenants', 'genre_id')) {
            Schema::table('apprenants', function (Blueprint $table) {
                $table->foreignId('genre_id')->nullable()
                    ->after('sexe')
                    ->constrained('genres')
                    ->nullOnDelete();
            });

            // Data migration : sexe → genre_id
            $mapping = [
                'M' => DB::table('genres')->where('code', 'M')->value('id'),
                'F' => DB::table('genres')->where('code', 'F')->value('id'),
            ];
            foreach ($mapping as $sexe => $genreId) {
                if ($genreId) {
                    DB::table('apprenants')
                        ->where('sexe', $sexe)
                        ->whereNull('genre_id')
                        ->update(['genre_id' => $genreId]);
                }
            }
        }

        // Enseignants
        if (Schema::hasTable('enseignants') && !Schema::hasColumn('enseignants', 'genre_id')) {
            Schema::table('enseignants', function (Blueprint $table) {
                $table->foreignId('genre_id')->nullable()
                    ->after('gender')
                    ->constrained('genres')
                    ->nullOnDelete();
            });

            $mapping = [
                'M'     => DB::table('genres')->where('code', 'M')->value('id'),
                'F'     => DB::table('genres')->where('code', 'F')->value('id'),
                'Autre' => DB::table('genres')->where('code', 'AUTRE')->value('id'),
            ];
            foreach ($mapping as $gender => $genreId) {
                if ($genreId) {
                    DB::table('enseignants')
                        ->where('gender', $gender)
                        ->whereNull('genre_id')
                        ->update(['genre_id' => $genreId]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('apprenants') && Schema::hasColumn('apprenants', 'genre_id')) {
            Schema::table('apprenants', function (Blueprint $table) {
                $table->dropForeign(['genre_id']);
                $table->dropColumn('genre_id');
            });
        }
        if (Schema::hasTable('enseignants') && Schema::hasColumn('enseignants', 'genre_id')) {
            Schema::table('enseignants', function (Blueprint $table) {
                $table->dropForeign(['genre_id']);
                $table->dropColumn('genre_id');
            });
        }
    }
};
