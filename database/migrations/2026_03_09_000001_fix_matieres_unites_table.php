<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matieres_unites', function (Blueprint $table) {
            // Add ecole_id if it doesn't exist
            if (!Schema::hasColumn('matieres_unites', 'ecole_id')) {
                $table->foreignId('ecole_id')->nullable()->after('libelle')->constrained('ecoles')->onDelete('set null');
            }

            // Remove pays_id if it exists
            if (Schema::hasColumn('matieres_unites', 'pays_id')) {
                $table->dropForeign(['pays_id']);
                $table->dropColumn('pays_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('matieres_unites', function (Blueprint $table) {
            // Add back pays_id if removing it
            if (!Schema::hasColumn('matieres_unites', 'pays_id')) {
                $table->foreignId('pays_id')->nullable()->after('coefficient')->constrained('pays')->onDelete('cascade');
            }

            // Remove ecole_id if adding it back
            if (Schema::hasColumn('matieres_unites', 'ecole_id')) {
                $table->dropForeign(['ecole_id']);
                $table->dropColumn('ecole_id');
            }
        });
    }
};
