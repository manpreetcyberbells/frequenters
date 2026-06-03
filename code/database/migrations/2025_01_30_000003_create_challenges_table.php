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
        Schema::create('challenges', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('challenge_name', 255)->notNullable();
            $table->enum('challenge_type', [
                'check_ins', 'businesses_visited', 'reviews', 
                'points_redeemed', 'referrals', 'badges'
            ])->notNullable();
            $table->text('description')->nullable();
            $table->integer('reward_points')->default(0);
            $table->dateTime('start_date')->notNullable();
            $table->dateTime('end_date')->notNullable();
            $table->boolean('is_active')->default(true);
            $table->enum('tier', ['beginner', 'intermediate', 'expert'])->notNullable();
            $table->unsignedBigInteger('target_city_id')->index(); // Indexed column
            $table->timestamps();

            // Optional foreign key constraint (if cities table exists)
            // $table->foreign('target_city_id')->references('id')->on('cities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
