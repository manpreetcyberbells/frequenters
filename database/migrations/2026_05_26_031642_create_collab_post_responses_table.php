<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collab_post_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collab_post_id')->constrained('collab_posts')->cascadeOnDelete();
            $table->foreignId('responder_business_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->timestamps();

            $table->unique(['collab_post_id', 'responder_business_id'], 'uq_collab_response');
            $table->index(['collab_post_id'], 'idx_collab_post');
            $table->index(['responder_business_id'], 'idx_responder_biz');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collab_post_responses');
    }
};