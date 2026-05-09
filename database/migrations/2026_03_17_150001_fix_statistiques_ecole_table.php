<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statistiques_ecole', function (Blueprint $table) {
            // Keep existing columns and add missing ones
            if (!Schema::hasColumn('statistiques_ecole', 'nombre_enseignants_permanent')) {
                $table->renameColumn('nombre_enseignants_permanent', 'nombre_enseignants_permanents');
            }
        });
    }

    public function down(): void
    {
        // Reverse the changes
    }
};
