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
        DB::statement("
            ALTER TABLE sessions_caisse
            MODIFY statut ENUM('ouverte', 'fermee', 'annulee', 'attente')
            NOT NULL DEFAULT 'fermee'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE sessions_caisse
            MODIFY statut ENUM('ouverte', 'fermee', 'annulee')
            NOT NULL DEFAULT 'ouverte'
        ");
    }
};
