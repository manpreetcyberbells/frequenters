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
        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('customer_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('reward_id'); // Foreign key to rewards table
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->integer('points_redeemed');
            $table->timestamp('redeemed_at')->default(now());
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('redemption_code', 50);
            $table->string('failure_reason', 255)->nullable();
            $table->enum('redemption_method', ['online', 'in_store'])->default('in_store');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('reward_id')
                  ->references('id')
                  ->on('rewards')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
    }
};
