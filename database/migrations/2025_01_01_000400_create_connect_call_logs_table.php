<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connect_call_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->string('from_number')->nullable();
            $table->string('to_number')->nullable();
            $table->string('twilio_call_sid')->nullable()->index();
            $table->string('call_status')->nullable();
            $table->boolean('auto_text_sent')->default(false);
            $table->string('auto_text_skipped_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connect_call_logs');
    }
};
