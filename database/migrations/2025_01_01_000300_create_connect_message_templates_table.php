<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connect_message_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            // missed_call_open | missed_call_closed | forward_customer_reply_to_owner
            // | multiple_conversation_warning | pause_confirmation
            // | resume_confirmation | help | status
            $table->string('template_type');
            $table->text('body');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'template_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connect_message_templates');
    }
};
