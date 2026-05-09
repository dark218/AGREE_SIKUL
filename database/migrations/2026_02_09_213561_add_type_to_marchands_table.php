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
        if (Schema::hasTable('marchands')) if (Schema::hasTable('marchands')) Schema::table('marchands', function (Blueprint $table) {
            $table->enum('type', ['informel', 'boutique', 'grande_surface'])
                  ->notNull()
                  ->default('informel')
                  ->after('identifiant_fiscal')
                  ->comment('Type de marchand: informel, boutique, grande_surface');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('marchands')) if (Schema::hasTable('marchands')) Schema::table('marchands', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
