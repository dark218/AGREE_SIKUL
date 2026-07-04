<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        // Rename table
        Schema::rename('modes_paiement', 'moyens_paiement');
    }

    public function down(): void
    {
        // Rename back
        Schema::rename('moyens_paiement', 'modes_paiement');
    }
};
