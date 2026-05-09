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
        Schema::table('enseignants', function (Blueprint $table) {
            // Make matricule nullable since we use num_enseignant instead
            if (Schema::hasColumn('enseignants', 'matricule')) {
                $table->string('matricule', 100)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            if (Schema::hasColumn('enseignants', 'matricule')) {
                $table->string('matricule', 100)->nullable(false)->change();
            }
        });
    }
};
