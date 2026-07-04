<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        try {
            DB::statement('ALTER TABLE `type_enseignement` DROP FOREIGN KEY `type_enseignement_pays_id_foreign`');
        } catch (\Exception $e) {
            // Foreign key might not exist, continue
        }

        try {
            DB::statement('ALTER TABLE `type_enseignement` DROP INDEX `type_enseignement_pays_id_etat_index`');
        } catch (\Exception $e) {
            // Index might not exist, continue
        }

        DB::statement('ALTER TABLE `type_enseignement` MODIFY `pays_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `type_enseignement` MODIFY `pays_id` BIGINT UNSIGNED NOT NULL');
    }
};
