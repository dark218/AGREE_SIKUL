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
        if (Schema::hasTable('otp_session')) return;
        Schema::create('otp_session', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("phone_number");
            $table->dateTime("start_date");
            $table->dateTime("end_date");
            $table->string("otp");
            $table->integer("tentative")->default(0);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_session');
    }
};
