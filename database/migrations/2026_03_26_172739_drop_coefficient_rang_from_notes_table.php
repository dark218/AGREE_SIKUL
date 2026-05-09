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
        if (Schema::hasTable('notes')) if (Schema::hasTable('notes')) Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(['coefficient', 'rang']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notes')) if (Schema::hasTable('notes')) Schema::table('notes', function (Blueprint $table) {
            $table->decimal('coefficient', 5, 2)->nullable()->after('note_max');
            $table->integer('rang')->nullable()->after('coefficient');
        });
    }
};
