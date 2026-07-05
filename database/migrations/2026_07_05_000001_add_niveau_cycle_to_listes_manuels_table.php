<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('listes_manuels')) {
            return;
        }

        Schema::table('listes_manuels', function (Blueprint $table) {
            if (!Schema::hasColumn('listes_manuels', 'niveau_id')) {
                $table->foreignId('niveau_id')->nullable()->after('section_id')->constrained('niveaux')->nullOnDelete();
            }
            if (!Schema::hasColumn('listes_manuels', 'cycle_id')) {
                $table->foreignId('cycle_id')->nullable()->after('niveau_id')->constrained('cycles_enseignement')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('listes_manuels')) {
            return;
        }

        Schema::table('listes_manuels', function (Blueprint $table) {
            if (Schema::hasColumn('listes_manuels', 'cycle_id')) {
                $table->dropConstrainedForeignId('cycle_id');
            }
            if (Schema::hasColumn('listes_manuels', 'niveau_id')) {
                $table->dropConstrainedForeignId('niveau_id');
            }
        });
    }
};
