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
        Schema::table('communes', function (Blueprint $table) {
            // Only add if they don't exist
            if (!Schema::hasColumn('communes', 'population')) {
                $table->unsignedBigInteger('population')->nullable()->default(0)->comment('Population de la commune');
            }
            if (!Schema::hasColumn('communes', 'surface')) {
                $table->decimal('surface', 10, 2)->nullable()->comment('Surface en km²');
            }
            if (!Schema::hasColumn('communes', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->comment('ID de l\'utilisateur qui a supprimé');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communes', function (Blueprint $table) {
            $table->dropColumnIfExists(['population', 'surface', 'deleted_by']);
        });
    }
};
