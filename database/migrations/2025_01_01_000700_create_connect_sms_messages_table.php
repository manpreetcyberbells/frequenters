<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connect_sms_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('connect_conversation_id')->nullable()->index();
            $table->string('from_number')->nullable();
            $table->string('to_number')->nullable();
            $table->text('body')->nullable();
            $table->string('direction');   // inbound | outbound
            $table->string('sender_type');  // customer | owner | system
            $table->string('twilio_message_sid')->nullable()->index();
            $table->string('status')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connect_sms_messages');
    }
};
