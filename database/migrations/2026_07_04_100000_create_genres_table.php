<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Module Genre — table de référentiel paramétrable pour le sexe/genre
 * des personnes (apprenants, enseignants, personnel administratif...).
 *
 * Avantage vs enum hard-codé :
 *  • Ajout de nouveaux genres sans migration (Autre, Non-binaire, etc.)
 *  • Traduction / libellé personnalisable par établissement
 *  • Statistiques et reporting propres (JOIN sur genre_id au lieu de
 *    strings hétérogènes)
 *
 * Migration idempotente + seed initial (Masculin / Féminin / Autre)
 * pour préserver la rétro-compatibilité.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('genres')) {
            Schema::create('genres', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();           // 'M', 'F', 'AUTRE' — utilisé pour compat legacy
                $table->string('libelle', 100);                 // 'Masculin', 'Féminin', 'Autre'
                $table->string('symbole', 5)->nullable();       // 'M', 'F' — pour affichage compact
                $table->string('couleur', 20)->nullable();      // ex '#0b5697' — pour graphs/badges
                $table->integer('ordre')->default(0);
                $table->enum('etat', ['actif', 'inactif'])->default('actif');

                // Traçabilité BaseModel
                $table->string('creation_username')->nullable();
                $table->string('creation_hostname')->nullable();
                $table->string('modification_username')->nullable();
                $table->string('modification_hostname')->nullable();
                $table->string('deletion_username')->nullable();
                $table->string('deletion_hostname')->nullable();
                $table->string('checksum')->nullable();
                $table->string('external_id')->nullable();
                $table->string('source_system')->default('Agree Sikul');

                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Seed initial — les 3 genres canoniques
        if (DB::table('genres')->count() === 0) {
            DB::table('genres')->insert([
                [
                    'code' => 'M', 'libelle' => 'Masculin', 'symbole' => 'M',
                    'couleur' => '#0b5697', 'ordre' => 1, 'etat' => 'actif',
                    'source_system' => 'Agree Sikul',
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'code' => 'F', 'libelle' => 'Féminin', 'symbole' => 'F',
                    'couleur' => '#e5590c', 'ordre' => 2, 'etat' => 'actif',
                    'source_system' => 'Agree Sikul',
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'code' => 'AUTRE', 'libelle' => 'Autre', 'symbole' => 'X',
                    'couleur' => '#64748b', 'ordre' => 3, 'etat' => 'actif',
                    'source_system' => 'Agree Sikul',
                    'created_at' => now(), 'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('genres');
    }
};
