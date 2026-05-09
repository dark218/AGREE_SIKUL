<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('institutions')) if (Schema::hasTable('institutions')) Schema::table('institutions', function (Blueprint $table) {
            // Change the enum to match form values
            $table->enum('statut', ['actif', 'inactif'])->default('actif')->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('institutions')) if (Schema::hasTable('institutions')) Schema::table('institutions', function (Blueprint $table) {
            // Revert to original enum
            $table->enum('statut', ['non_actif', 'actif', 'suspendu', 'bloque'])->default('actif')->change();
        });
    }
};
