<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ecolages')) {
            return;
        }

        Schema::table('ecolages', function (Blueprint $table) {
            if (!Schema::hasColumn('ecolages', 'section_id')) {
                $table->foreignId('section_id')
                    ->nullable()
                    ->after('campus_id')
                    ->constrained('sections')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('ecolages', 'cycle_id')) {
                $table->foreignId('cycle_id')
                    ->nullable()
                    ->after('section_id')
                    ->constrained('cycles_enseignement')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ecolages')) {
            return;
        }

        Schema::table('ecolages', function (Blueprint $table) {
            if (Schema::hasColumn('ecolages', 'cycle_id')) {
                $table->dropConstrainedForeignId('cycle_id');
            }
            if (Schema::hasColumn('ecolages', 'section_id')) {
                $table->dropConstrainedForeignId('section_id');
            }
        });
    }
};
