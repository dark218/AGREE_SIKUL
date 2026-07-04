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
        Schema::table('communes', function (Blueprint $table) {
            if (!Schema::hasColumn('communes', 'region_id')) {
                $table->foreignId('region_id')->nullable()->after('departement_id')->constrained('regions')->onDelete('set null');
            }
            if (!Schema::hasColumn('communes', 'pays_id')) {
                $table->foreignId('pays_id')->nullable()->after('region_id')->constrained('pays')->onDelete('set null');
            }
            if (!Schema::hasColumn('communes', 'population')) {
                $table->integer('population')->nullable()->after('pays_id');
            }
            if (!Schema::hasColumn('communes', 'surface')) {
                $table->decimal('surface', 10, 2)->nullable()->after('population');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communes', function (Blueprint $table) {
            if (Schema::hasColumn('communes', 'region_id')) {
                $table->dropForeignIfExists(['region_id']);
                $table->dropColumn('region_id');
            }
            if (Schema::hasColumn('communes', 'pays_id')) {
                $table->dropForeignIfExists(['pays_id']);
                $table->dropColumn('pays_id');
            }
            if (Schema::hasColumn('communes', 'population')) {
                $table->dropColumn('population');
            }
            if (Schema::hasColumn('communes', 'surface')) {
                $table->dropColumn('surface');
            }
        });
    }
};
