<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuditFieldsToAllTables extends Migration
{
    public function up()
    {
        $tables = ['module', 'feature', 'permissions', 'role', 'role_permissions', 'users', 'pays', 'zones', 'devises', 'fichier', 'fournisseurs_paiement', 'pays_devises'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {

                    // Champs de traçabilité
                    if (!Schema::hasColumn($tableName, 'checksum')) {
                        $table->string('checksum')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'external_id')) {
                        $table->string('external_id')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'source_system')) {
                        $table->string('source_system')->default('smilpay');
                    }

                    // Champs d'audit
                    if (!Schema::hasColumn($tableName, 'creation_hostname')) {
                        $table->string('creation_hostname')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'modification_hostname')) {
                        $table->string('modification_hostname')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'deletion_hostname')) {
                        $table->string('deletion_hostname')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'creation_username')) {
                        $table->string('creation_username')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'modification_username')) {
                        $table->string('modification_username')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'deletion_username')) {
                        $table->string('deletion_username')->nullable();
                    }

                    // Index pour les performances
                    // Récupère les index existants pour éviter la recréation.
                    $existingIndexes = collect(
                        \DB::select("SHOW INDEX FROM `{$tableName}`")
                    )->pluck('Key_name')->toArray();
                    if (!in_array("{$tableName}_external_id_index", $existingIndexes, true)) {
                        $table->index(['external_id']);
                    }
                    if (!in_array("{$tableName}_source_system_index", $existingIndexes, true)) {
                        $table->index(['source_system']);
                    }
                });
            }
        }
    }

    public function down()
    {
        $tables = ['module', 'feature', 'permissions', 'role', 'role_permissions', 'users', 'pays', 'zones', 'devises', 'fichier', 'fournisseurs_paiement', 'pays_devises'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->engine = 'InnoDB';
                    $table->dropColumn([
                        'checksum', 'external_id', 'source_system',
                        'creation_hostname', 'modification_hostname', 'deletion_hostname',
                        'creation_username', 'modification_username', 'deletion_username'
                    ]);
                });
            }
        }
    }
};
