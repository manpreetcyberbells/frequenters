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
        Schema::create('reward_conditions', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('reward_id'); // Foreign key to rewards table
            $table->enum('condition_type', ['expiry', 'usage_limit']);
            $table->string('value', 255)->nullable();
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
        Schema::dropIfExists('reward_conditions');
    }
};
