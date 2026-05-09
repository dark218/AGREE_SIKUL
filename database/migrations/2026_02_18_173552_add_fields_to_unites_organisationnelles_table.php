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
        if (Schema::hasTable('unites_organisationnelles')) Schema::table('unites_organisationnelles', function (Blueprint $table) {
            // Add missing columns
            $table->string('type_unite')->nullable()->after('libelle');
            $table->foreignId('responsable_id')->nullable()->after('type_unite')->constrained('users')->onDelete('set null');
            $table->decimal('budget_annuel', 12, 2)->nullable()->after('responsable_id');
            $table->integer('effectif_max')->nullable()->after('budget_annuel');
            $table->integer('niveau_hierarchique')->nullable()->after('effectif_max');
            $table->foreignId('ecole_id')->nullable()->after('niveau_hierarchique')->constrained('ecoles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback not needed
    }
};
