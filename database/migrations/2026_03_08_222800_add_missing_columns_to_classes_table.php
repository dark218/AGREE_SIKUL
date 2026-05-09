<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // Only add campus_id if it doesn't exist
            if (!Schema::hasColumn('classes', 'campus_id')) {
                $table->foreignId('campus_id')->nullable()->after('niveau_id')->constrained('campuses')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'campus_id')) {
                $table->dropForeignIfExists(['campus_id']);
                $table->dropColumn('campus_id');
            }
        });
    }
};
