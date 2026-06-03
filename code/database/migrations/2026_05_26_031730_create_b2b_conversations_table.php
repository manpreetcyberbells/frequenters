<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collab_post_id')->nullable()->constrained('collab_posts')->nullOnDelete();
            $table->foreignId('business_a_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('business_b_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['collab_post_id', 'business_a_id', 'business_b_id'], 'uq_b2b_conv');
            $table->index(['business_a_id'], 'idx_b2b_biz_a');
            $table->index(['business_b_id'], 'idx_b2b_biz_b');
        });

        Schema::create('b2b_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('b2b_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_messages');
        Schema::dropIfExists('b2b_conversations');
    }
};