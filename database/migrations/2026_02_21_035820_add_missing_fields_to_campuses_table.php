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
        // idempotence guard
        Schema::table('campuses', function (Blueprint $table) {
            if (!Schema::hasColumn('campuses', 'boite_postale')) {
                $table->string('boite_postale')->nullable()->after('code_postal');
            }
            if (!Schema::hasColumn('campuses', 'quartier')) {
                $table->string('quartier')->nullable()->after('boite_postale');
            }
            if (!Schema::hasColumn('campuses', 'commune')) {
                $table->string('commune')->nullable()->after('quartier');
            }
            if (!Schema::hasColumn('campuses', 'departement')) {
                $table->string('departement')->nullable()->after('commune');
            }
            if (!Schema::hasColumn('campuses', 'region')) {
                $table->string('region')->nullable()->after('departement');
            }
            if (!Schema::hasColumn('campuses', 'pays_id')) {
                $table->unsignedBigInteger('pays_id')->nullable()->after('region');
                $table->foreign('pays_id')->references('id')->on('pays')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campuses', function (Blueprint $table) {
            $table->dropForeign(['pays_id']);
            $table->dropColumn('boite_postale');
            $table->dropColumn('quartier');
            $table->dropColumn('commune');
            $table->dropColumn('departement');
            $table->dropColumn('region');
            $table->dropColumn('pays_id');
        });
    }
};
