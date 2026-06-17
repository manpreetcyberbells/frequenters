<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connect_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('connect_customer_id')->index();
            $table->string('conversation_code')->nullable()->index();
            $table->string('status')->default('new'); // new | active | closed
            $table->unsignedBigInteger('started_from_call_log_id')->nullable();
            $table->timestamp('last_customer_message_at')->nullable();
            $table->timestamp('last_owner_message_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connect_conversations');
    }
};
