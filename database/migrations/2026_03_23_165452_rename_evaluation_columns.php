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
        // idempotence guard
        DB::statement('ALTER TABLE evaluations CHANGE COLUMN date_evaluation `date` DATE NULL');
        DB::statement('ALTER TABLE evaluations CHANGE COLUMN type_evaluation `type` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE evaluations CHANGE COLUMN note_sur `sur` DECIMAL(8,2) NOT NULL DEFAULT 20');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE evaluations CHANGE COLUMN `date` date_evaluation DATE NULL');
        DB::statement('ALTER TABLE evaluations CHANGE COLUMN `type` type_evaluation ENUM("controle_continu","devoir","examen","projet","oral") DEFAULT "controle_continu"');
        DB::statement('ALTER TABLE evaluations CHANGE COLUMN `sur` note_sur DECIMAL(8,2) NOT NULL DEFAULT 20');
    }
};
