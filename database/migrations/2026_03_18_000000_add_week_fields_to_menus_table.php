<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menus')) if (Schema::hasTable('menus')) Schema::table('menus', function (Blueprint $table) {
            $table->date('week_start_date')->nullable()->after('id');
            $table->date('week_end_date')->nullable()->after('week_start_date');
            $table->string('week_name')->nullable()->after('week_end_date');
            $table->enum('jour', ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'])->nullable()->after('week_name');
            $table->enum('statut', ['actif', 'inactif'])->default('actif')->after('prix_cents');

            $table->index(['week_start_date', 'jour']);
            $table->index('statut');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) if (Schema::hasTable('menus')) Schema::table('menus', function (Blueprint $table) {
            $table->dropIndex(['week_start_date', 'jour']);
            $table->dropIndex(['statut']);
            $table->dropColumn(['week_start_date', 'week_end_date', 'week_name', 'jour', 'statut']);
        });
    }
};
