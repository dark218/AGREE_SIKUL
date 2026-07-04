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
        Schema::table('devoirs', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('devoirs', 'matiere_id')) {
                $table->unsignedBigInteger('matiere_id')->nullable()->after('id');
                $table->foreign('matiere_id')->references('id')->on('matieres')->nullOnDelete();
            }

            if (!Schema::hasColumn('devoirs', 'classe_id')) {
                $table->unsignedBigInteger('classe_id')->nullable()->after('matiere_id');
                $table->foreign('classe_id')->references('id')->on('classes')->nullOnDelete();
            }

            if (!Schema::hasColumn('devoirs', 'date_remise')) {
                $table->date('date_remise')->nullable()->after('date_limite');
            }

            if (!Schema::hasColumn('devoirs', 'coefficient')) {
                $table->decimal('coefficient', 5, 2)->default(1)->after('date_remise');
            }

            if (!Schema::hasColumn('devoirs', 'statut')) {
                $table->enum('statut', ['actif', 'inactif'])->default('actif')->after('coefficient');
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
