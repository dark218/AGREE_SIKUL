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
        Schema::table('enseignants', function (Blueprint $table) {
            // Add basic identity fields if not exist
            if (!Schema::hasColumn('enseignants', 'num_enseignant')) {
                $table->string('num_enseignant', 50)->unique()->after('user_id');
            }
            if (!Schema::hasColumn('enseignants', 'nom')) {
                $table->string('nom', 100)->after('num_enseignant');
            }
            if (!Schema::hasColumn('enseignants', 'prenoms')) {
                $table->string('prenoms', 100)->nullable()->after('nom');
            }
            if (!Schema::hasColumn('enseignants', 'email')) {
                $table->string('email', 100)->nullable()->after('prenoms');
            }
            if (!Schema::hasColumn('enseignants', 'telephone')) {
                $table->string('telephone', 20)->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            if (Schema::hasColumn('enseignants', 'num_enseignant')) {
                $table->dropUnique(['num_enseignant']);
                $table->dropColumn('num_enseignant');
            }
            $table->dropColumn([
                'nom', 'prenoms', 'email', 'telephone'
            ]);
        });
    }
};
