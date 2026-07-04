<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('categories_fournitures')) Schema::hasTable('categories_fournitures') ? null : Schema::create('categories_fournitures', function (Blueprint $table) {
            $table->id();
            $table->string('libelle')->unique();
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->string('checksum')->nullable();
            $table->string('external_id')->nullable();
            $table->string('source_system')->nullable();
            $table->string('creation_hostname')->nullable();
            $table->string('modification_hostname')->nullable();
            $table->string('deletion_hostname')->nullable();
            $table->string('creation_username')->nullable();
            $table->string('modification_username')->nullable();
            $table->string('deletion_username')->nullable();

            $table->index(['external_id']);
            $table->index(['source_system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories_fournitures');
    }
};
