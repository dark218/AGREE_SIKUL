<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (Schema::hasTable('natures_examens')) if (Schema::hasTable('natures_examens')) Schema::table('natures_examens', function (Blueprint $table) {
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null')->after('pays_id');
            $table->decimal('note_eliminatoire', 5, 2)->nullable()->after('poids');
            $table->integer('duree_minutes')->nullable()->after('note_eliminatoire');
            $table->boolean('est_eliminatoire')->default(false)->after('duree_minutes');
            $table->boolean('est_rattrapage')->default(false)->after('est_eliminatoire');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('natures_examens')) if (Schema::hasTable('natures_examens')) Schema::table('natures_examens', function (Blueprint $table) {
            $table->dropForeignIfExists(['ecole_id']);
            $table->dropColumn(['ecole_id', 'note_eliminatoire', 'duree_minutes', 'est_eliminatoire', 'est_rattrapage']);
        });
    }
};
