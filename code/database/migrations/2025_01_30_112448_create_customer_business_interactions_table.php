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
        Schema::create('customer_business_interactions', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->enum('interaction_type', ['check_in', 'reward_redeem']);
            $table->integer('points_allocated')->default(0);
            $table->timestamp('last_interaction_date')->useCurrent();
            $table->enum('interaction_subtype', ['event_rsvp', 'purchase', 'review', 'feedback', 'promo_redemption'])->nullable();
            $table->integer('interaction_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_business_interactions');
    }
};
