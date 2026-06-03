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
        Schema::create('customer_business_relationships', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            // $table->enum('customer_tier', ['new', 'repeat', 'regular', 'daily'])->default('new');
            $table->enum('customer_tier', ['new', 'regular', 'daily'])->default('new')->change();
            $table->dateTime('last_interaction_date')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('interaction_count')->default(0);
            $table->integer('total_points_earned')->default(0);
            $table->integer('total_points_redeemed')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_business_relationships');
    }
};
