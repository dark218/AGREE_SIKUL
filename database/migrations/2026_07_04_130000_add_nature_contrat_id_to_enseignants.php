<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('enseignants') && !Schema::hasColumn('enseignants', 'nature_contrat_id')) {
            Schema::table('enseignants', function (Blueprint $table) {
                $table->foreignId('nature_contrat_id')->nullable()
                    ->after('type_contrat')
                    ->constrained('natures_contrats')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('enseignants') && Schema::hasColumn('enseignants', 'nature_contrat_id')) {
            Schema::table('enseignants', function (Blueprint $table) {
                $table->dropForeign(['nature_contrat_id']);
                $table->dropColumn('nature_contrat_id');
            });
        }
    }
};
