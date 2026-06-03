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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('referrer_id'); // Foreign key to users table
            $table->unsignedBigInteger('referred_user_id')->nullable(); // Can be null if the user hasn't signed up yet
            $table->string('referred_email', 255);
            $table->dateTime('referral_date')->default(now());
            $table->boolean('reward_given')->default(0);
            $table->integer('reward_points')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('referrer_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('referred_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
