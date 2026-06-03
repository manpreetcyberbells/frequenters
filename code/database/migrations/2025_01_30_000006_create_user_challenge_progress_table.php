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
        Schema::create('user_challenge_progress', function (Blueprint $table) {
            $table->id('progress_id'); // Primary key
            $table->unsignedBigInteger('user_id'); // Foreign key referencing users table
            $table->unsignedBigInteger('challenge_id'); // Foreign key referencing challenges table
            $table->integer('progress_count'); // Progress count (int11 equivalent)
            $table->boolean('reward_claimed')->default(0); // Reward claimed (default: 0)
            $table->dateTime('completion_date')->nullable(); // Completion date (nullable)
            $table->timestamps(); // created_at and updated_at fields

            // Define foreign key constraints
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade'); // Cascade on delete

            $table->foreign('challenge_id')
                  ->references('id')
                  ->on('challenges')
                  ->onDelete('cascade'); // Cascade on delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_challenge_progress');
    }
};
