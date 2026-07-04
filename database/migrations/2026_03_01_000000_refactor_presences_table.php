<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // idempotence guard
        Schema::table('presences', function (Blueprint $table) {
            // Remove redundant columns
            $table->dropColumn(['present', 'retard']);

            // Add new useful columns
            $table->time('heure_arrivee')->nullable()->after('statut');
            $table->text('remarques')->nullable()->after('heure_arrivee');
            $table->foreignId('saisi_par')->nullable()->after('remarques')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presences', function (Blueprint $table) {
            // Restore removed columns only if they don't exist
            if (!Schema::hasColumn('presences', 'present')) {
                $table->boolean('present')->default(false)->after('statut');
            }
            if (!Schema::hasColumn('presences', 'retard')) {
                $table->boolean('retard')->default(false)->after('present');
            }
        });

        // Drop foreign key constraint using raw SQL
        try {
            DB::statement("ALTER TABLE presences DROP FOREIGN KEY presences_saisi_par_foreign");
        } catch (\Exception $e) {
            // Ignore if constraint doesn't exist
        }

        Schema::table('presences', function (Blueprint $table) {
            // Remove added columns
            if (Schema::hasColumn('presences', 'heure_arrivee')) {
                $table->dropColumn(['heure_arrivee']);
            }
            if (Schema::hasColumn('presences', 'remarques')) {
                $table->dropColumn(['remarques']);
            }
            if (Schema::hasColumn('presences', 'saisi_par')) {
                $table->dropColumn(['saisi_par']);
            }
        });
    }
};
