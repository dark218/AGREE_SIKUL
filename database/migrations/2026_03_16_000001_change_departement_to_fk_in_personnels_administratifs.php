<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personnels_administratifs')) if (Schema::hasTable('personnels_administratifs')) Schema::table('personnels_administratifs', function (Blueprint $table) {
            $table->dropColumn('departement');
            $table->foreignId('departement_id')->nullable()->constrained('departements')->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('personnels_administratifs')) if (Schema::hasTable('personnels_administratifs')) Schema::table('personnels_administratifs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('departement_id');
            $table->string('departement', 100)->nullable();
        });
    }
};
