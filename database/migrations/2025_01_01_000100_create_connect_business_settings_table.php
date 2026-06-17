<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connect_business_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();

            $table->string('existing_public_phone')->nullable();
            $table->string('assigned_twilio_phone')->nullable()->unique();
            $table->string('owner_cell_phone')->nullable()->index();
            $table->string('owner_email')->nullable();
            $table->string('timezone')->default('America/New_York');

            $table->boolean('connect_enabled')->default(true);
            $table->boolean('auto_text_enabled')->default(true);
            $table->boolean('owner_sms_relay_enabled')->default(true);

            $table->unsignedInteger('duplicate_text_window_hours')->default(24);
            $table->unsignedInteger('active_conversation_window_minutes')->default(30);
            $table->boolean('require_code_when_multiple_active')->default(true);
            $table->boolean('send_owner_notification_on_customer_reply')->default(true);
            $table->boolean('pause_mode_enabled')->default(false);

            $table->string('status')->default('active'); // active | inactive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connect_business_settings');
    }
};
