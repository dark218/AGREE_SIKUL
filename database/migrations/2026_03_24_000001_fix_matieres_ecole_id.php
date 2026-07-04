<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (Schema::hasTable('matieres')) if (Schema::hasTable('matieres')) Schema::table('matieres', function (Blueprint $table) {
            // Drop old foreign key first
            $table->dropForeign(['ecole_id']);
            
            // Make ecole_id nullable so creation doesn't fail
            $table->unsignedBigInteger('ecole_id')->nullable()->change();
            
            // Add proper foreign key to ecoles table
            $table->foreign('ecole_id')->references('id')->on('ecoles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('matieres')) if (Schema::hasTable('matieres')) Schema::table('matieres', function (Blueprint $table) {
            // Drop new foreign key
            $table->dropForeign(['ecole_id']);
            
            // Revert to original
            $table->unsignedBigInteger('ecole_id')->change();
            
            // Add back old (wrong) foreign key
            $table->foreign('ecole_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
