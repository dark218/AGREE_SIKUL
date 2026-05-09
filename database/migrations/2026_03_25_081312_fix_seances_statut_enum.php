<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE seances MODIFY COLUMN statut ENUM('planifiee', 'realisee', 'annulee', 'reportee') DEFAULT 'planifiee'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE seances MODIFY COLUMN statut ENUM('programmee', 'realizee', 'annulee', 'reportee') DEFAULT 'programmee'");
    }
};
