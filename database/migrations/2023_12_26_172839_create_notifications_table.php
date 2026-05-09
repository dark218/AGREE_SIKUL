<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('body', 255);
            $table->morphs('notifiable');
            $table->string('type');
            $table->text('data');
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_read')->default(false);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('read_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
