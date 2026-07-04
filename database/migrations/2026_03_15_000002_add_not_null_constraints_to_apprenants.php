<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        // First, ensure all rows have valid nom and prenoms
        DB::table('apprenants')
            ->whereNull('nom')
            ->orWhere('nom', '')
            ->delete();

        DB::table('apprenants')
            ->whereNull('prenoms')
            ->orWhere('prenoms', '')
            ->delete();

        Schema::table('apprenants', function (Blueprint $table) {
            // Add NOT NULL constraints to required fields
            if (Schema::hasColumn('apprenants', 'nom')) {
                $table->string('nom', 255)->nullable(false)->change();
            }
            if (Schema::hasColumn('apprenants', 'prenoms')) {
                $table->string('prenoms', 255)->nullable(false)->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('apprenants', function (Blueprint $table) {
            $table->string('nom', 255)->nullable()->change();
            $table->string('prenoms', 255)->nullable()->change();
        });
    }
};
