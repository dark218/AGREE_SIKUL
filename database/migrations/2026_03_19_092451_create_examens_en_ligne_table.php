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
        // Cette table est créée par 2026_03_19_create_exam_modules_table.php
        // Cette migration ne fait rien (elle était incorrecte - utilisait Schema::table sur une table inexistante)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examens_en_ligne');
    }
};
