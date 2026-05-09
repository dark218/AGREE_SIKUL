<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecoles')) if (Schema::hasTable('ecoles')) Schema::table('ecoles', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('set null')->after('campus_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('ecoles')) if (Schema::hasTable('ecoles')) Schema::table('ecoles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institution_id');
        });
    }
};
