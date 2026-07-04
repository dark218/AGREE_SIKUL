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
            // Make cours_id nullable to avoid "Field doesn't have a default value" error
            // Since we're using matiere_id and classe_id instead
            if (Schema::hasColumn('devoirs', 'cours_id')) {
                $table->unsignedBigInteger('cours_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devoirs', function (Blueprint $table) {
            if (Schema::hasColumn('devoirs', 'cours_id')) {
                $table->unsignedBigInteger('cours_id')->nullable(false)->change();
            }
        });
    }
};
