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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // Nullable for broadcast
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['email', 'SMS', 'push'])->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->text('message');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->integer('retry_count')->default(0);
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->timestamp('scheduled_time')->nullable();
            $table->boolean('is_broadcast')->default(false); // New column for broadcast support
            $table->timestamps();

            $table->foreign('campaign_id')
                ->references('id')
                ->on('campaigns')
                ->onDelete('cascade');
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
